<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClientPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.spaceship.key' => 'test-key',
            'services.spaceship.secret' => 'test-secret',
            'services.spaceship.base_url' => 'https://spaceship.dev/api/v1',
        ]);
    }

    protected function customerWithDomain(): array
    {
        $customer = User::factory()->create();

        $domain = Domain::create([
            'name' => 'customer-site.com',
            'user_id' => $customer->id,
            'lifecycle_status' => 'registered',
            'expires_at' => now()->addYear(),
        ]);

        return [$customer, $domain];
    }

    public function test_guests_are_redirected_to_client_login(): void
    {
        $this->get('/client')->assertRedirect('/client/login');
    }

    public function test_customers_without_roles_can_access_the_client_panel(): void
    {
        [$customer] = $this->customerWithDomain();

        $this->actingAs($customer)->get('/client')->assertOk();
    }

    public function test_customers_still_cannot_access_the_admin_panel(): void
    {
        [$customer] = $this->customerWithDomain();

        $this->actingAs($customer)->get('/admin')->assertForbidden();
    }

    public function test_customers_see_only_their_own_domains(): void
    {
        [$customer] = $this->customerWithDomain();

        Domain::create([
            'name' => 'someone-elses.com',
            'user_id' => User::factory()->create()->id,
            'lifecycle_status' => 'registered',
        ]);

        $this->actingAs($customer)
            ->get('/client/domains')
            ->assertOk()
            ->assertSee('customer-site.com')
            ->assertDontSee('someone-elses.com');
    }

    public function test_customer_can_open_dns_page_for_own_domain(): void
    {
        Http::fake([
            '*/dns/records/*' => Http::response([
                'items' => [
                    ['type' => 'A', 'name' => '@', 'address' => '203.0.113.10', 'ttl' => 3600],
                ],
                'total' => 1,
            ]),
        ]);

        [$customer, $domain] = $this->customerWithDomain();

        $this->actingAs($customer)
            ->get("/client/domains/{$domain->id}/dns")
            ->assertOk()
            ->assertSee('203.0.113.10');
    }

    public function test_customer_cannot_open_dns_page_for_foreign_domain(): void
    {
        [$customer] = $this->customerWithDomain();

        $foreign = Domain::create([
            'name' => 'someone-elses.com',
            'user_id' => User::factory()->create()->id,
        ]);

        $this->actingAs($customer)
            ->get("/client/domains/{$foreign->id}/dns")
            ->assertNotFound();
    }
}
