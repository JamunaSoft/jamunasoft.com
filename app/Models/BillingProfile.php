<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * WHMCS-style billing contact: one client account (one login, one owner)
 * can invoice under several companies, each with its own name/address.
 */
class BillingProfile extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'contact_name', 'email', 'phone',
        'address', 'city', 'postal_code', 'country',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
