<?php

namespace App\Services;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Jobs\PollDomainOrderOperation;
use App\Jobs\ProcessDomainOrder;
use App\Mail\DomainOrderAdminNotification;
use App\Mail\DomainOrderCompleted;
use App\Mail\DomainOrderConfirmation;
use App\Models\Domain;
use App\Models\DomainOrder;
use App\Models\Tld;
use App\Models\User;
use App\Services\Spaceship\DefaultContactService;
use App\Services\Spaceship\DomainSyncService;
use App\Services\Spaceship\SpaceshipClient;
use App\Services\Spaceship\SpaceshipException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DomainOrderService
{
    public function __construct(
        protected SpaceshipClient $client,
        protected DomainSyncService $syncService,
        protected DefaultContactService $contactService,
    ) {}

    /**
     * Create an order in pending-payment state. Amount is computed from the
     * TLD price table unless explicitly given (admin manual orders).
     *
     * @param  array{name: string, email: string, phone?: ?string, user_id?: ?int}  $customer
     */
    public function create(
        array $customer,
        string $domainName,
        DomainOrderType $type,
        int $years = 1,
        ?float $amount = null,
    ): DomainOrder {
        $domainName = strtolower(trim($domainName));

        if ($amount === null) {
            $tld = Tld::matching($domainName);

            if ($tld === null) {
                throw new \InvalidArgumentException("No active pricing found for the TLD of {$domainName}.");
            }

            $amount = $tld->priceFor($type) * $years;
        }

        $order = DomainOrder::create([
            'reference' => DomainOrder::generateReference(),
            'user_id' => $customer['user_id'] ?? null,
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'] ?? null,
            'domain_name' => $domainName,
            'type' => $type,
            'years' => $years,
            'amount' => $amount,
            'currency' => 'BDT',
            'status' => DomainOrderStatus::PendingPayment,
        ]);

        // Every order is billed through an invoice; the order confirmation
        // email already carries the payment instructions, so no separate
        // invoice email. Guest orders get invoiced once a user exists.
        if ($order->user_id !== null) {
            app(InvoiceService::class)->create(
                userId: $order->user_id,
                items: [[
                    'description' => sprintf(
                        'Domain %s — %s (%d %s)',
                        strtolower($order->type->getLabel()),
                        $order->domain_name,
                        $order->years,
                        str('year')->plural($order->years),
                    ),
                    'unit_price' => (float) $order->amount,
                    'item_type' => 'domain_order',
                    'item_id' => $order->id,
                ]],
                dueAt: now()->addDays(7),
                sendEmail: false,
            );
        }

        $this->sendOrderEmails($order);

        return $order;
    }

    /**
     * Record payment and queue registrar-side processing. Also settles the
     * linked invoice unless the payment came in through the invoice itself.
     */
    public function markPaid(DomainOrder $order, ?string $method = null, ?string $reference = null, bool $settleInvoice = true): void
    {
        $order->update([
            'status' => DomainOrderStatus::Paid,
            'payment_method' => $method,
            'payment_reference' => $reference,
            'paid_at' => now(),
            'error_message' => null,
        ]);

        if ($settleInvoice) {
            $invoiceService = app(InvoiceService::class);
            $invoice = $invoiceService->openInvoiceFor('domain_order', $order->id);

            if ($invoice !== null) {
                $invoiceService->recordPayment($invoice, $invoice->balance(), $method, $reference, processSideEffects: false);
            }
        }

        ProcessDomainOrder::dispatch($order);
    }

    /**
     * Execute the registrar-side operation for a paid order. Runs in a queued
     * job; on SpaceshipException the order is marked failed for admin retry.
     */
    public function process(DomainOrder $order): void
    {
        $order->update(['status' => DomainOrderStatus::Processing]);

        try {
            $result = match ($order->type) {
                DomainOrderType::Register => $this->processRegistration($order),
                DomainOrderType::Renew => $this->processRenewal($order),
                DomainOrderType::Transfer => throw new SpaceshipException('Transfer orders are not automated yet — handle manually.'),
            };
        } catch (SpaceshipException $e) {
            $this->fail($order, $e->getMessage());

            return;
        }

        $order->update(['spaceship_operation_id' => $result['operationId']]);

        PollDomainOrderOperation::dispatch($order)->delay(now()->addSeconds(10));
    }

    /**
     * Called once the registrar operation succeeds (or was synchronous):
     * sync the domain locally, attach the customer, close the order.
     */
    public function complete(DomainOrder $order): void
    {
        $domain = $this->syncService->syncByName($order->domain_name);

        // Guest orders get a client-panel account keyed by email, so the
        // customer can manage the domain at /client (via password reset).
        if ($order->user_id === null) {
            $user = User::firstOrCreate(
                ['email' => $order->customer_email],
                ['name' => $order->customer_name, 'password' => str()->password(32)],
            );

            $order->user_id = $user->id;
            $order->save();
        }

        if ($domain->user_id === null) {
            $domain->update(['user_id' => $order->user_id]);
        }

        $order->update([
            'status' => DomainOrderStatus::Completed,
            'completed_at' => now(),
            'error_message' => null,
        ]);

        try {
            Mail::to($order->customer_email)->queue(new DomainOrderCompleted($order));
        } catch (\Throwable $e) {
            Log::warning('Domain order completion email failed: '.$e->getMessage(), ['order' => $order->reference]);
        }
    }

    public function fail(DomainOrder $order, string $message): void
    {
        $order->update([
            'status' => DomainOrderStatus::Failed,
            'error_message' => $message,
        ]);

        Log::error('Domain order failed: '.$message, ['order' => $order->reference]);
    }

    /**
     * @return array{operationId: ?string}
     */
    protected function processRegistration(DomainOrder $order): array
    {
        $availability = $this->client->checkAvailability($order->domain_name);

        if (! (bool) data_get($availability, 'isAvailable', data_get($availability, 'available', false))) {
            throw new SpaceshipException("{$order->domain_name} is no longer available for registration.");
        }

        if ((bool) data_get($availability, 'isPremium', false)) {
            throw new SpaceshipException("{$order->domain_name} is a premium domain — register manually after checking the premium price.");
        }

        $contactId = $this->contactService->contactId();

        $result = $this->client->registerDomain($order->domain_name, [
            'registrant' => $contactId,
            'admin' => $contactId,
            'tech' => $contactId,
            'billing' => $contactId,
        ], years: $order->years, autoRenew: false, privacyLevel: 'high');

        return ['operationId' => $result['operationId']];
    }

    /**
     * @return array{operationId: ?string}
     */
    protected function processRenewal(DomainOrder $order): array
    {
        $domain = Domain::query()->where('name', $order->domain_name)->first();

        if ($domain?->expires_at === null) {
            throw new SpaceshipException("{$order->domain_name} is not in the local domain list — sync from Spaceship before renewing.");
        }

        $result = $this->client->renewDomain(
            $order->domain_name,
            $order->years,
            $domain->expires_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
        );

        return ['operationId' => $result['operationId']];
    }

    protected function sendOrderEmails(DomainOrder $order): void
    {
        try {
            Mail::to($order->customer_email)->queue(new DomainOrderConfirmation($order));

            foreach ($this->notificationRecipients() as $recipient) {
                Mail::to($recipient)->queue(new DomainOrderAdminNotification($order));
            }
        } catch (\Throwable $e) {
            // Never let a broken mail configuration lose an order.
            Log::warning('Domain order email dispatch failed: '.$e->getMessage(), ['order' => $order->reference]);
        }
    }

    /** @return array<int, string> */
    public function notificationRecipients(): array
    {
        $configured = (string) settings('domain_order_recipients', settings('lead_notification_recipients', ''));

        return collect(explode(',', $configured))
            ->map(fn (string $email) => trim($email))
            ->filter(fn (string $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->values()
            ->all();
    }
}
