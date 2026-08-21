<?php

namespace Tests\Feature;

use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkedDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_foreign_hosts_get_the_parked_page(): void
    {
        $this->get('http://customersite.com/')
            ->assertOk()
            ->assertSee('customersite.com')
            ->assertSee('coming soon');
    }

    public function test_own_host_serves_the_website(): void
    {
        $this->get('/')->assertOk()->assertDontSee('ready to launch');
    }

    public function test_default_nameservers_fall_back_to_the_cluster(): void
    {
        $this->assertSame(['cl1.jamunasoft.com', 'cl2.jamunasoft.com'], default_nameservers());

        Settings::set(['domain_default_nameservers' => "NS1.Example.com\nns2.example.com\n"], 'domains');
        $this->assertSame(['ns1.example.com', 'ns2.example.com'], default_nameservers());

        // A single nameserver is not enough — fall back to the cluster pair.
        Settings::set(['domain_default_nameservers' => 'ns1.example.com'], 'domains');
        $this->assertSame(['cl1.jamunasoft.com', 'cl2.jamunasoft.com'], default_nameservers());
    }
}
