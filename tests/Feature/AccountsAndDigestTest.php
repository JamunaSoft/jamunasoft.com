<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Mail\DailyDigest;
use App\Mail\InvoiceCreated;
use App\Models\EmailLog;
use App\Models\Expense;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoiceService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountsAndDigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_digest_summarizes_the_last_24_hours(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $invoice = app(InvoiceService::class)->create(
            userId: $user->id,
            items: [['title' => 'Hosting', 'unit_price' => 5000]],
            sendEmail: false,
        );
        app(InvoiceService::class)->recordPayment($invoice, 2000, 'bkash', 'TRX1');

        Expense::create([
            'expensed_at' => now(),
            'category' => ExpenseCategory::ServerHosting,
            'description' => 'VPS bill',
            'amount' => 1500,
        ]);

        $this->artisan('billing:daily-digest')->assertSuccessful();

        Mail::assertQueued(DailyDigest::class, function (DailyDigest $mail) {
            return $mail->hasTo(config('mail.billing_bcc'))
                && $mail->data['invoicesCreated'] === 1
                && $mail->data['paymentsReceived'] === 1
                && $mail->data['paymentsTotal'] === 2000.0
                && $mail->data['expensesRecorded'] === 1500.0
                && $mail->data['unpaidCount'] === 1
                && $mail->data['unpaidTotal'] === 3000.0;
        });
    }

    public function test_daily_digest_can_be_disabled(): void
    {
        Mail::fake();
        Settings::set(['digest_recipients' => 'off']);

        $this->artisan('billing:daily-digest')->assertSuccessful();

        Mail::assertNotQueued(DailyDigest::class);
        Settings::flush();
    }

    public function test_email_log_flips_to_sent_when_the_mail_actually_goes_out(): void
    {
        $user = User::factory()->create();
        $invoice = app(InvoiceService::class)->create(
            userId: $user->id,
            items: [['title' => 'Hosting', 'unit_price' => 5000]],
            sendEmail: false,
        );

        $mailable = new InvoiceCreated($invoice);
        $log = EmailLog::create([
            'user_id' => $user->id,
            'type' => 'invoice_created',
            'subject' => $mailable->envelope()->subject,
            'recipient' => $user->email,
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        Mail::to($user->email)->sendNow($mailable);

        $log->refresh();
        $this->assertSame('sent', $log->status);
        $this->assertNotNull($log->sent_at);
    }

    public function test_ledger_combines_payments_and_expenses(): void
    {
        $user = User::factory()->create();
        $invoice = app(InvoiceService::class)->create(
            userId: $user->id,
            items: [['title' => 'Hosting', 'unit_price' => 5000]],
            sendEmail: false,
        );

        Mail::fake();
        app(InvoiceService::class)->recordPayment($invoice, 5000, 'bkash', 'TRX9');

        Expense::create([
            'expensed_at' => now(),
            'category' => ExpenseCategory::ServerHosting,
            'description' => 'VPS bill',
            'vendor_id' => Vendor::create(['name' => 'Hetzner'])->id,
            'amount' => 1500,
        ]);

        $entries = LedgerEntry::all();

        $this->assertCount(2, $entries);
        $this->assertSame(3500.0, (float) $entries->sum('signed_amount'));

        $in = $entries->firstWhere('direction', 'in');
        $out = $entries->firstWhere('direction', 'out');
        $this->assertSame($user->name, $in->counterparty);
        $this->assertSame($invoice->reference, $in->invoice_reference);
        $this->assertSame('Hetzner', $out->counterparty);
        $this->assertSame(ExpenseCategory::ServerHosting, $out->category);
    }

    public function test_vendor_previous_balance_tracks_repayments(): void
    {
        $vendor = Vendor::create(['name' => 'Shohoz Motion', 'opening_balance' => 5000]);

        $this->assertSame(5000.0, $vendor->previousBalanceRemaining());

        Expense::create([
            'expensed_at' => now(),
            'category' => ExpenseCategory::PreviousDue,
            'description' => 'Old due — part payment',
            'vendor_id' => $vendor->id,
            'amount' => 2000,
        ]);

        // Regular payments do not touch the previous balance.
        Expense::create([
            'expensed_at' => now(),
            'category' => ExpenseCategory::Outsourcing,
            'description' => 'September videos',
            'vendor_id' => $vendor->id,
            'amount' => 8000,
        ]);

        $this->assertSame(3000.0, $vendor->refresh()->previousBalanceRemaining());
    }

    public function test_expense_totals_feed_the_reports(): void
    {
        Expense::create([
            'expensed_at' => now(),
            'category' => ExpenseCategory::Marketing,
            'description' => 'FB boosting budget',
            'amount' => 30000,
        ]);

        $this->assertSame(30000.0, (float) Expense::whereBetween('expensed_at', [now()->startOfMonth(), now()])->sum('amount'));
        $this->assertSame(ExpenseCategory::Marketing, Expense::first()->category);
    }
}
