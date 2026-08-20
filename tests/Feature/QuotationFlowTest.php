<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Mail\QuotationResponded;
use App\Mail\QuotationSent;
use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationService;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuotationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function makeQuotation(array $attributes = []): Quotation
    {
        $quotation = Quotation::create([
            'reference' => Quotation::generateReference(),
            'token' => str()->random(40),
            'customer_name' => 'Karim Ahmed',
            'customer_email' => 'karim@example.com',
            'status' => QuotationStatus::Draft,
            'valid_until' => now()->addDays(14),
            ...$attributes,
        ]);

        $quotation->items()->create(['description' => 'Corporate website', 'quantity' => 1, 'unit_price' => 50000]);
        $quotation->items()->create(['description' => 'Hosting (1 year)', 'quantity' => 1, 'unit_price' => 5000]);

        return app(QuotationService::class)->recalculateTotals($quotation);
    }

    public function test_totals_are_computed(): void
    {
        $quotation = $this->makeQuotation();

        $this->assertSame('55000.00', (string) $quotation->total);
    }

    public function test_sending_emails_the_customer_with_public_link(): void
    {
        Mail::fake();

        $quotation = $this->makeQuotation();

        app(QuotationService::class)->send($quotation);

        $this->assertSame(QuotationStatus::Sent, $quotation->refresh()->status);
        Mail::assertQueued(QuotationSent::class, fn (QuotationSent $mail) => $mail->hasTo('karim@example.com'));
    }

    public function test_public_page_requires_the_token(): void
    {
        $quotation = $this->makeQuotation(['status' => QuotationStatus::Sent]);

        $this->get("/quotation/{$quotation->reference}/{$quotation->token}")
            ->assertOk()
            ->assertSee('Corporate website')
            ->assertSee('55,000');

        $this->get("/quotation/{$quotation->reference}/wrong-token")->assertNotFound();
    }

    public function test_draft_quotations_are_not_public(): void
    {
        $quotation = $this->makeQuotation();

        $this->get("/quotation/{$quotation->reference}/{$quotation->token}")->assertNotFound();
    }

    public function test_customer_can_accept_and_admin_is_notified(): void
    {
        Mail::fake();
        Settings::set(['lead_notification_recipients' => 'sales@jamunasoft.com'], 'website');

        $quotation = $this->makeQuotation(['status' => QuotationStatus::Sent]);

        $this->post("/quotation/{$quotation->reference}/{$quotation->token}", ['decision' => 'accept'])
            ->assertRedirect($quotation->publicUrl());

        $this->assertSame(QuotationStatus::Accepted, $quotation->refresh()->status);
        Mail::assertQueued(QuotationResponded::class, fn (QuotationResponded $mail) => $mail->hasTo('sales@jamunasoft.com'));
    }

    public function test_expired_quotation_cannot_be_accepted(): void
    {
        $quotation = $this->makeQuotation([
            'status' => QuotationStatus::Sent,
            'valid_until' => now()->subDays(3),
        ]);

        $this->post("/quotation/{$quotation->reference}/{$quotation->token}", ['decision' => 'accept']);

        $this->assertSame(QuotationStatus::Sent, $quotation->refresh()->status);
    }

    public function test_accepted_quotation_converts_to_invoice_and_creates_the_client(): void
    {
        Mail::fake();

        $quotation = $this->makeQuotation(['status' => QuotationStatus::Accepted]);

        $invoice = app(QuotationService::class)->convertToInvoice($quotation);

        $this->assertSame('55000.00', (string) $invoice->total);
        $this->assertSame(InvoiceStatus::Unpaid, $invoice->status);
        $this->assertCount(2, $invoice->items);

        $user = User::where('email', 'karim@example.com')->firstOrFail();
        $this->assertSame($user->id, $invoice->user_id);
        $this->assertSame($invoice->id, $quotation->refresh()->invoice_id);

        // Converting is idempotent via the visible-action guard; the user is reused.
        $this->assertSame(1, User::where('email', 'karim@example.com')->count());
    }
}
