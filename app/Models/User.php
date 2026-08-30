<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPasswordNotification;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Filament uses panel-specific reset routes, unlike Laravel's default
     * password.reset route. This keeps links correct for admin and client.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $notification = app(FilamentResetPasswordNotification::class, ['token' => $token]);
        $notification->url = Filament::getResetPasswordUrl($token, $this);

        $this->notify($notification);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function domainOrders(): HasMany
    {
        return $this->hasMany(DomainOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(ClientService::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    /**
     * Only users holding at least one admin-panel role may sign in to /admin.
     * The client panel is open to every authenticated user (customers have
     * no roles, which also keeps them out of /admin).
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'client') {
            return true;
        }

        return $this->roles()->exists();
    }
}
