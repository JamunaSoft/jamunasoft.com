<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'user_id', 'lifecycle_status', 'verification_status',
        'auto_renew', 'is_premium', 'privacy_level', 'nameserver_provider',
        'nameservers', 'contact_ids', 'epp_statuses', 'registered_at',
        'expires_at', 'last_synced_at', 'last_reminder_days', 'meta', 'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'auto_renew' => 'boolean',
            'is_premium' => 'boolean',
            'nameservers' => 'array',
            'contact_ids' => 'array',
            'epp_statuses' => 'array',
            'registered_at' => 'datetime',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeExpiringWithin(Builder $query, int $days): Builder
    {
        return $query
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addDays($days)]);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('user_id');
    }
}
