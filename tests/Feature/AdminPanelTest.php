<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function superAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_users_without_roles_cannot_access_the_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_super_admin_can_view_dashboard(): void
    {
        $this->actingAs($this->superAdmin())->get('/admin')->assertOk();
    }

    public function test_super_admin_can_view_resource_pages(): void
    {
        $admin = $this->superAdmin();

        $urls = [
            '/admin/services', '/admin/services/create', '/admin/service-categories',
            '/admin/solutions', '/admin/solutions/create',
            '/admin/portfolios', '/admin/portfolios/create', '/admin/portfolio-categories',
            '/admin/packages', '/admin/packages/create',
            '/admin/hosting-plans', '/admin/hosting-plans/create',
            '/admin/testimonials', '/admin/team-members',
            '/admin/blog-posts', '/admin/blog-posts/create', '/admin/blog-categories', '/admin/blog-tags',
            '/admin/faqs', '/admin/pages', '/admin/pages/create', '/admin/menus',
            '/admin/leads', '/admin/leads/create', '/admin/contact-messages',
            '/admin/newsletter-subscribers', '/admin/redirects', '/admin/social-links',
            '/admin/users', '/admin/users/create', '/admin/roles',
            '/admin/website-settings', '/admin/homepage-content',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_content_manager_cannot_manage_leads_but_sales_can(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $content = User::factory()->create();
        $content->assignRole('Content Manager');

        $sales = User::factory()->create();
        $sales->assignRole('Sales Manager');

        $this->actingAs($content)->get('/admin/leads')->assertForbidden();
        $this->actingAs($sales)->get('/admin/leads')->assertOk();

        $this->actingAs($content)->get('/admin/services')->assertOk();
    }
}
