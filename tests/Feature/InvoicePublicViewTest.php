<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceCreated;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePublicViewTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(InvoiceStatus $status = InvoiceStatus::Unpaid): Invoice
    {
        $invoice = Invoice::create([
            'reference' => Invoice::generateReference(),
            'user_id' => User::factory()->create()->id,
            'status' => $status,
            'subtotal' => 4500,
            'total' => 4500,
            'due_at' => now()->addDays(7),
        ]);

        $invoice->items()->create([
            'title' => 'Web Hosting — Basic',
            'description' => 'domain: example.com',
            'quantity' => 1,
            'unit_price' => 4500,
            'total' => 4500,
        ]);

        return $invoice;
    }

    public function test_invoice_gets_a_token_on_creation(): void
    {
        $this->assertNotNull($this->makeInvoice()->token);
    }

    public function test_public_page_shows_the_invoice_with_a_valid_token(): void
    {
        $invoice = $this->makeInvoice();

        $this->get($invoice->publicUrl())
            ->assertOk()
            ->assertSee($invoice->reference)
            ->assertSee('Web Hosting — Basic')
            ->assertSee('4,500.00');
    }

    public function test_wrong_token_is_rejected(): void
    {
        $invoice = $this->makeInvoice();

        $this->get(route('invoice.show', ['reference' => $invoice->reference, 'token' => 'wrong-token']))
            ->assertNotFound();
    }

    public function test_draft_invoices_are_not_publicly_visible(): void
    {
        $invoice = $this->makeInvoice(InvoiceStatus::Draft);

        $this->get($invoice->publicUrl())->assertNotFound();
    }

    public function test_public_pdf_download_works_with_the_token(): void
    {
        $invoice = $this->makeInvoice();

        $this->get(route('invoice.pdf.public', ['reference' => $invoice->reference, 'token' => $invoice->token]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_email_links_to_the_public_page(): void
    {
        $invoice = $this->makeInvoice();

        $this->assertStringContainsString(
            $invoice->publicUrl(),
            (new InvoiceCreated($invoice))->render(),
        );
    }
}
