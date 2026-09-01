<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceCreated;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function makeInvoice(User $user, float $amount, string $title): Invoice
    {
        $invoice = Invoice::create([
            'reference' => Invoice::generateReference(),
            'user_id' => $user->id,
            'status' => InvoiceStatus::Unpaid,
            'subtotal' => $amount,
            'total' => $amount,
            'due_at' => now()->addDays(rand(5, 20)),
        ]);

        $invoice->items()->create([
            'title' => $title,
            'quantity' => 1,
            'unit_price' => $amount,
            'total' => $amount,
        ]);

        return $invoice;
    }

    public function test_merges_unpaid_invoices_of_one_client_into_the_oldest(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $older = $this->makeInvoice($user, 6000, 'VPS Hosting');
        $older->update(['created_at' => now()->subDays(3), 'due_at' => now()->addDays(20)]);

        $newer = $this->makeInvoice($user, 1800, 'Domain Renewal');
        $newer->update(['due_at' => now()->addDays(5)]);
        app(InvoiceService::class)->recordPayment($newer, 800, 'bkash', 'TRX-1');

        $target = app(InvoiceService::class)->merge(collect([$older->refresh(), $newer->refresh()]));

        $this->assertSame($older->id, $target->id);
        $this->assertCount(2, $target->items);
        $this->assertSame('7800.00', (string) $target->total);
        $this->assertSame('800.00', (string) $target->amount_paid);
        $this->assertSame(7000.0, $target->balance());
        $this->assertCount(1, $target->payments);
        $this->assertTrue($target->due_at->isSameDay(now()->addDays(5)));
        $this->assertStringContainsString($newer->reference, (string) $target->notes);

        $newer->refresh();
        $this->assertSame(InvoiceStatus::Cancelled, $newer->status);
        $this->assertSame('0.00', (string) $newer->total);
        $this->assertCount(0, $newer->items);
        $this->assertCount(0, $newer->payments);
        $this->assertStringContainsString("Merged into {$target->reference}", (string) $newer->notes);

        // Merging never emails the client on its own.
        Mail::assertNotQueued(InvoiceCreated::class);
    }

    public function test_invoices_of_different_clients_cannot_be_merged(): void
    {
        $a = $this->makeInvoice(User::factory()->create(), 1000, 'A');
        $b = $this->makeInvoice(User::factory()->create(), 2000, 'B');

        $this->expectException(\InvalidArgumentException::class);
        app(InvoiceService::class)->merge(collect([$a, $b]));
    }

    public function test_paid_invoices_cannot_be_merged(): void
    {
        $user = User::factory()->create();
        $unpaid = $this->makeInvoice($user, 1000, 'A');
        $paid = $this->makeInvoice($user, 2000, 'B');
        $paid->update(['status' => InvoiceStatus::Paid, 'amount_paid' => 2000, 'paid_at' => now()]);

        $this->expectException(\InvalidArgumentException::class);
        app(InvoiceService::class)->merge(collect([$unpaid, $paid->refresh()]));
    }

    public function test_a_single_invoice_cannot_be_merged(): void
    {
        $invoice = $this->makeInvoice(User::factory()->create(), 1000, 'A');

        $this->expectException(\InvalidArgumentException::class);
        app(InvoiceService::class)->merge(collect([$invoice]));
    }
}
