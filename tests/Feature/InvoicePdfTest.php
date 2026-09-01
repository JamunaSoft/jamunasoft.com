<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceCreated;
use App\Mail\InvoicePaid;
use App\Mail\InvoiceReminder;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(User $user, InvoiceStatus $status = InvoiceStatus::Unpaid): Invoice
    {
        $invoice = Invoice::create([
            'reference' => Invoice::generateReference(),
            'user_id' => $user->id,
            'status' => $status,
            'subtotal' => 6000,
            'total' => 6000,
            'due_at' => now()->addDays(7),
        ]);

        $invoice->items()->create([
            'description' => "VPS Hosting\nDomain: example.com",
            'quantity' => 1,
            'unit_price' => 6000,
            'total' => 6000,
        ]);

        return $invoice;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $invoice = $this->makeInvoice(User::factory()->create());

        $this->get(route('invoices.pdf', $invoice))->assertRedirect('/client/login');
    }

    public function test_owner_can_download_their_invoice_pdf(): void
    {
        $user = User::factory()->create();
        $invoice = $this->makeInvoice($user);

        $response = $this->actingAs($user)->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_other_clients_cannot_download_someone_elses_invoice(): void
    {
        $invoice = $this->makeInvoice(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('invoices.pdf', $invoice))
            ->assertForbidden();
    }

    public function test_clients_cannot_download_draft_invoices(): void
    {
        $user = User::factory()->create();
        $invoice = $this->makeInvoice($user, InvoiceStatus::Draft);

        $this->actingAs($user)->get(route('invoices.pdf', $invoice))->assertForbidden();
    }

    public function test_staff_can_download_any_invoice_including_drafts(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $invoice = $this->makeInvoice(User::factory()->create(), InvoiceStatus::Draft);

        $this->actingAs($admin)
            ->get(route('invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_download_flag_forces_an_attachment_response(): void
    {
        $user = User::factory()->create();
        $invoice = $this->makeInvoice($user);

        $this->actingAs($user)
            ->get(route('invoices.pdf', ['invoice' => $invoice, 'download' => 1]))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename='.$invoice->reference.'.pdf');
    }

    public function test_invoice_emails_attach_the_pdf(): void
    {
        $invoice = $this->makeInvoice(User::factory()->create());

        foreach ([
            new InvoiceCreated($invoice),
            new InvoiceReminder($invoice),
            new InvoicePaid($invoice),
        ] as $mailable) {
            $attachments = $mailable->attachments();

            $this->assertCount(1, $attachments);
            $this->assertSame($invoice->reference.'.pdf', $attachments[0]->as);
            $this->assertSame('application/pdf', $attachments[0]->mime);
        }
    }

    public function test_item_title_and_description_display(): void
    {
        $invoice = $this->makeInvoice(User::factory()->create());

        // Pre-title rows fall back to splitting the description.
        $legacy = $invoice->items->first();
        $this->assertSame('VPS Hosting', $legacy->displayTitle());
        $this->assertSame('Domain: example.com', $legacy->displayDescription());

        $item = $invoice->items()->create([
            'title' => 'SSL Certificate',
            'quantity' => 1,
            'unit_price' => 1500,
            'total' => 1500,
        ]);
        $this->assertSame('SSL Certificate', $item->displayTitle());
        $this->assertNull($item->displayDescription());
    }

    public function test_items_follow_their_sort_order(): void
    {
        $invoice = $this->makeInvoice(User::factory()->create());
        $invoice->items()->update(['sort_order' => 2]);
        $invoice->items()->create([
            'title' => 'Should come first',
            'sort_order' => 1,
            'quantity' => 1,
            'unit_price' => 100,
            'total' => 100,
        ]);

        $this->assertSame('Should come first', $invoice->refresh()->items->first()->title);
    }

    public function test_taka_in_words_spells_out_amounts(): void
    {
        $this->assertSame('Seven Thousand Eight Hundred Taka Only', taka_in_words(7800.0));
        $this->assertSame('Six Thousand Taka and Fifty Paisa Only', taka_in_words(6000.50));
    }
}
