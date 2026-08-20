<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_the_reports_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Header widgets render lazily via Livewire, so test them directly.
        $this->actingAs($admin)->get('/admin/reports')->assertOk();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\Reports\ReportsStatsOverview::class)
            ->assertSee('Revenue this month');
    }

    public function test_roles_without_reports_permission_are_blocked(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $support = User::factory()->create();
        $support->assignRole('Support Manager');

        $this->actingAs($support)->get('/admin/reports')->assertForbidden();
    }
}
