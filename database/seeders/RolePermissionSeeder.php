<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Every content area gets a `<key>.view` and `<key>.manage` permission.
     */
    public const AREAS = [
        'services', 'solutions', 'portfolio', 'packages', 'hosting',
        'testimonials', 'team', 'blog', 'faqs', 'pages', 'menus',
        'redirects', 'social-links', 'leads', 'contact-messages',
        'newsletter', 'settings', 'users', 'roles',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::AREAS as $area) {
            Permission::findOrCreate("{$area}.view");
            Permission::findOrCreate("{$area}.manage");
        }

        // Super Admin bypasses all checks via Gate::before (see AppServiceProvider).
        Role::findOrCreate('Super Admin');

        $admin = Role::findOrCreate('Admin');
        $admin->syncPermissions(
            Permission::where('name', 'not like', 'roles.%')->get()
        );

        $contentAreas = ['services', 'solutions', 'portfolio', 'blog', 'faqs', 'testimonials', 'pages', 'team', 'packages'];
        $content = Role::findOrCreate('Content Manager');
        $content->syncPermissions(
            collect($contentAreas)->flatMap(fn ($area) => ["{$area}.view", "{$area}.manage"])->all()
        );

        $sales = Role::findOrCreate('Sales Manager');
        $sales->syncPermissions([
            'leads.view', 'leads.manage',
            'contact-messages.view', 'contact-messages.manage',
            'newsletter.view', 'newsletter.manage',
        ]);

        $support = Role::findOrCreate('Support Manager');
        $support->syncPermissions([
            'leads.view',
            'contact-messages.view',
        ]);
    }
}
