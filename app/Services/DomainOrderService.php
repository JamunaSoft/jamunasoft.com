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
use App\Services\Registrars\RegistrarException;
use App\Services\Registrars\RegistrarManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DomainOrderService
{
    public function __construct(protected RegistrarManager $registrars) {}

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

        // New registrations go through the admin-selected registrar; renewals
        // must use whichever registrar already holds the domain.
        $registrar = $type === DomainOrderType::Renew
            ? (Domain::query()->where('name', $domainName)->value('registrar') ?? $this->registrars->activeKey())
            : $this->registrars->activeKey();

        $order = DomainOrder::create([
            'reference' => DomainOrder::generateReference(),
            'user_id' => $customer['user_id'] ?? null,
            'customer_name' => $customer['name'],
            'customer_email' => $customer['email'],
            'customer_phone' => $customer['phone'] ?? null,
            'domain_name' => $domainName,
            'registrar' => $registrar,
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
                    'title' => sprintf(
                        'Domain %s — %s (%d %s)',
                        strtolower($order->type->getLabel()),
                        $order->domain_name,
                        $order->years,
                        str('year')->plural($order->years),
                    ),
                    'description' => $this->renewalPeriodLine($order),
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
                DomainOrderType::Transfer => throw new RegistrarException('Transfer orders are not automated yet — handle manually.'),
            };
        } catch (RegistrarException $e) {
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
        $domain = $this->registrars->for($order->registrar)->syncDomain($order->domain_name);

        // Fresh registrations get pointed at our default nameservers, so the
        // domain immediately serves the hosting cluster's default page.
        if ($order->type === DomainOrderType::Register) {
            $defaults = default_nameservers();
            $current = array_map('strtolower', (array) $domain->nameservers);

            if ($defaults !== [] && array_diff($defaults, $current) !== []) {
                try {
                    $this->registrars->for($order->registrar)->updateNameservers($order->domain_name, $defaults);
                    $domain->update(['nameservers' => $defaults, 'nameserver_provider' => 'custom']);
                } catch (RegistrarException $e) {
                    // Registration itself succeeded — a human can fix NS later.
                    Log::warning('Default nameserver setup failed: '.$e->getMessage(), ['order' => $order->reference]);
                }
            }
        }

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
        $registrar = $this->registrars->for($order->registrar);

        $availability = $registrar->checkAvailability($order->domain_name);

        if (! $availability['available']) {
            throw new RegistrarException("{$order->domain_name} is no longer available for registration.");
        }

        if ($availability['premium']) {
            throw new RegistrarException("{$order->domain_name} is a premium domain — register manually after checking the premium price.");
        }

        return $registrar->register($order->domain_name, $order->years);
    }

    /**
     * @return array{operationId: ?string}
     */
    protected function processRenewal(DomainOrder $order): array
    {
        return $this->registrars->for($order->registrar)->renew($order->domain_name, $order->years);
    }

    /**
     * "Duration: …" line for renewal invoices: the new period runs from the
     * domain's current expiry. Null for registrations/transfers (their
     * period only starts once the registrar completes the order).
     */
    public function renewalPeriodLine(DomainOrder $order): ?string
    {
        if ($order->type !== DomainOrderType::Renew) {
            return null;
        }

        $expiry = Domain::where('name', $order->domain_name)->value('expires_at');

        if ($expiry === null) {
            return null;
        }

        $start = Carbon::parse($expiry);
        $end = $start->copy()->addYears((int) $order->years)->subDay();

        return sprintf('Duration: %s – %s', $start->format('M d, Y'), $end->format('M d, Y'));
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
