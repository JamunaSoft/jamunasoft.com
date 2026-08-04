<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Roles/permissions are required for the app to work; demo content is
     * safe to run repeatedly (idempotent) and clearly marked as demo copy.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
