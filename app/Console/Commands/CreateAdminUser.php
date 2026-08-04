<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin
        {--name= : Full name of the administrator}
        {--email= : Email address used to sign in}
        {--password= : Password (omit to be prompted securely)}';

    protected $description = 'Create (or promote) a Super Admin user for the Filament admin panel';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password (min 8 characters)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            ['name' => 'required|string|max:255', 'email' => 'required|email', 'password' => 'required|string|min:8'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $role = Role::findOrCreate('Super Admin');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = Hash::make($password);
        $user->email_verified_at ??= now();
        $user->save();

        $user->assignRole($role);

        $this->info(($user->wasRecentlyCreated ? 'Created' : 'Updated')." Super Admin: {$email}");
        $this->line('You can now sign in at /admin');

        return self::SUCCESS;
    }
}
