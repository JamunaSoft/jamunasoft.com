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

#[Fillable(['name', 'email', 'secondary_email', 'password', 'company_name', 'phone', 'address', 'city', 'postal_code', 'country', 'admin_notes'])]
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

    /**
     * Label for client dropdowns: the name with the company appended,
     * e.g. "Parveen Akter — Caring Hands BD".
     */
    public function selectLabel(): string
    {
        return filled($this->company_name)
            ? "{$this->name} — {$this->company_name}"
            : $this->name;
    }

    /**
     * The client's phone in wa.me format (digits only, with country code).
     * Bangladeshi numbers get 880 prefixed; already-international numbers
     * pass through. Null when no phone is on file.
     */
    public function whatsappNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone) ?: '';

        return match (true) {
            $digits === '' => null,
            str_starts_with($digits, '880') => $digits,
            str_starts_with($digits, '0') => '880'.substr($digits, 1),
            strlen($digits) === 10 && str_starts_with($digits, '1') => '880'.$digits,
            default => $digits,
        };
    }

    /**
     * All inboxes that should receive billing/service emails: the login
     * email plus the optional secondary (billing contact) email.
     *
     * @return array<int, string>
     */
    public function billingEmails(): array
    {
        return array_values(array_unique(array_filter([
            $this->email,
            $this->secondary_email,
        ])));
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

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
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
