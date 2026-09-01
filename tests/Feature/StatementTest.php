<?php

namespace Tests\Feature;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use App\Models\Vendor;
use App\Services\InvoiceService;
use App\Services\StatementService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StatementTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_statement_carries_forward_and_runs_a_balance(): void
    {
        Mail::fake();
        $client = User::factory()->create();
        $service = app(InvoiceService::class);

        // Before the period: billed 5000, paid 2000 → carry forward 3000.
        $old = $service->create($client->id, [['title' => 'Old work', 'unit_price' => 5000]], sendEmail: false);
        $old->forceFill(['created_at' => now()->subDays(40)])->save();
        $service->recordPayment($old, 2000, 'bkash', 'OLD-TRX');
        $old->payments()->update(['paid_at' => now()->subDays(35)]);

        // Inside the period: billed 4000, paid 1000.
        $new = $service->create($client->id, [['title' => 'New work', 'unit_price' => 4000]], sendEmail: false);
        $service->recordPayment($new, 1000, 'bank', 'NEW-TRX');

        $statement = app(StatementService::class)->forClient($client, now()->subDays(10), now());

        $this->assertSame(3000.0, $statement['carryForward']);
        $this->assertCount(2, $statement['rows']);
        $this->assertSame(4000.0, $statement['totalDebit']);
        $this->assertSame(1000.0, $statement['totalCredit']);
        $this->assertSame(6000.0, $statement['closing']);
        $this->assertSame(6000.0, end($statement['rows'])['balance']);

        // Full history: no carry forward, closing identical.
        $full = app(StatementService::class)->forClient($client, null, null);
        $this->assertSame(0.0, $full['carryForward']);
        $this->assertCount(4, $full['rows']);
        $this->assertSame(6000.0, $full['closing']);
    }

    public function test_vendor_statement_tracks_previous_due(): void
    {
        $vendor = Vendor::create(['name' => 'Shohoz Motion', 'opening_balance' => 5000]);

        Expense::create([
            'expensed_at' => now()->subDays(5),
            'category' => ExpenseCategory::PreviousDue,
            'description' => 'Old due part payment',
            'vendor_id' => $vendor->id,
            'amount' => 2000,
        ]);
        Expense::create([
            'expensed_at' => now()->subDays(2),
            'category' => ExpenseCategory::Outsourcing,
            'description' => 'September videos',
            'vendor_id' => $vendor->id,
            'amount' => 8000,
        ]);

        $statement = app(StatementService::class)->forVendor($vendor, null, null);

        $this->assertSame(5000.0, $statement['carryForward']);
        $this->assertSame(10000.0, $statement['totalPaid']);
        $this->assertSame(3000.0, $statement['closing']);
    }

    public function test_statement_pdfs_are_staff_only(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $client = User::factory()->create();
        $vendor = Vendor::create(['name' => 'Hetzner']);

        $this->actingAs($admin)->get(route('statements.client', ['user' => $client->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($admin)->get(route('statements.vendor', ['vendor' => $vendor->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($client)->get(route('statements.client', ['user' => $client->id]))->assertForbidden();
    }
}
