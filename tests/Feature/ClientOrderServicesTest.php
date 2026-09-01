<?php

namespace Tests\Feature;

use App\Enums\HostingPlanType;
use App\Filament\Client\Pages\OrderServices;
use App\Filament\Client\Widgets\RenewalAlerts;
use App\Models\Domain;
use App\Models\HostingPlan;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientOrderServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function makePlan(): HostingPlan
    {
        return HostingPlan::create([
            'name' => 'BDIX Basic',
            'type' => HostingPlanType::cases()[0],
            'yearly_price' => 4500,
            'storage' => '10 GB',
            'is_active' => true,
        ]);
    }

    public function test_order_services_page_lists_active_products(): void
    {
        $this->makePlan();
        $client = User::factory()->create();

        $this->actingAs($client)
            ->get('/client/order-services')
            ->assertOk()
            ->assertSee('BDIX Basic');
    }

    public function test_requesting_a_service_opens_a_ticket(): void
    {
        $plan = $this->makePlan();
        $client = User::factory()->create();

        Livewire::actingAs($client)
            ->test(OrderServices::class)
            ->callAction('requestService', data: [
                'domain' => 'example.com',
                'notes' => 'Need it this week',
            ], arguments: ['type' => 'hosting', 'id' => $plan->id]);

        $ticket = Ticket::where('user_id', $client->id)->first();

        $this->assertNotNull($ticket);
        $this->assertSame('Order request: BDIX Basic', $ticket->subject);
        $this->assertStringContainsString('example.com', $ticket->messages()->first()->message);
    }

    public function test_renewal_alert_widget_shows_only_when_something_is_due(): void
    {
        $client = User::factory()->create();
        $this->actingAs($client);

        $this->assertFalse(RenewalAlerts::canView());

        Domain::create([
            'name' => 'example.com',
            'user_id' => $client->id,
            'expires_at' => now()->addDays(10),
        ]);

        $this->assertTrue(RenewalAlerts::canView());
    }
}
