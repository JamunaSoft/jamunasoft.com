<?php

namespace Tests\Feature;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Filament\Resources\DomainOrderResource\Pages\ListDomainOrders;
use App\Models\DomainOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class OperationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_is_gated_and_only_shows_authorized_workspaces(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->get('/admin/operations')->assertRedirect('/admin/login');
        $content = User::factory()->create();
        $content->assignRole('Content Manager');
        $this->actingAs($content)->get('/admin/operations')->assertForbidden();
        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager');
        $this->actingAs($sales)->get('/admin/operations')->assertOk()
            ->assertSee('Overdue follow-ups')->assertDontSee('Delivery &amp; background tasks', false)
            ->assertDontSee('Failed domain orders');
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $this->actingAs($admin)->get('/admin/operations')->assertOk()->assertSee('Failed domain orders');
    }

    public function test_read_only_domain_staff_cannot_confirm_or_retry_orders(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Queue::fake();
        $viewer = User::factory()->create();
        $viewer->assignRole('Content Manager');
        $viewer->givePermissionTo('domains.view');
        $order = DomainOrder::create([
            'reference' => DomainOrder::generateReference(), 'domain_name' => 'example.com',
            'customer_name' => 'Owner', 'customer_email' => 'owner@example.test',
            'type' => DomainOrderType::Transfer, 'registrar' => 'spaceship',
            'amount' => 1600, 'years' => 1, 'status' => DomainOrderStatus::PendingPayment,
        ]);
        Livewire::actingAs($viewer)->test(ListDomainOrders::class)->assertTableActionHidden('confirmPayment', $order);
        $this->assertSame(DomainOrderStatus::PendingPayment, $order->fresh()->status);
        $order->update(['status' => DomainOrderStatus::Failed]);
        Livewire::actingAs($viewer)->test(ListDomainOrders::class)->assertTableActionHidden('retry', $order);
        Queue::assertNothingPushed();
    }

    public function test_manual_transfer_check_only_reads_status_and_is_rate_limited(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        config(['services.spaceship.key' => 'test', 'services.spaceship.secret' => 'test']);
        Http::fake(['*/transfer' => Http::response(['status' => 'pending', 'direction' => 'in'])]);
        $order = DomainOrder::create([
            'reference' => DomainOrder::generateReference(), 'domain_name' => 'example.com',
            'customer_name' => 'Owner', 'customer_email' => 'owner@example.test',
            'type' => DomainOrderType::Transfer, 'registrar' => 'spaceship',
            'amount' => 1600, 'years' => 1, 'status' => DomainOrderStatus::Processing,
        ]);
        $component = Livewire::actingAs($admin)->test(ListDomainOrders::class);
        $component->callTableAction('checkTransfer', $order);
        $component->callTableAction('checkTransfer', $order->fresh());
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->method() === 'GET');
        $this->assertSame(DomainOrderStatus::Processing, $order->fresh()->status);
        $this->assertSame('pending', data_get($order->fresh()->meta, 'transfer_status'));
    }

    public function test_domain_order_creation_keeps_auth_code(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Livewire::actingAs($admin)->test(ListDomainOrders::class)->callAction('create', data: [
            'type' => 'transfer', 'domain_name' => 'migration-example.com',
            'customer_name' => 'Domain Owner', 'customer_email' => 'owner@example.test',
            'years' => 1, 'amount' => 1600, 'meta' => ['epp_code' => 'TEST-EPP-CODE'],
        ])->assertHasNoActionErrors();
        $order = DomainOrder::where('domain_name', 'migration-example.com')->firstOrFail();
        $this->assertSame('TEST-EPP-CODE', data_get($order->meta, 'epp_code'));
    }
}
