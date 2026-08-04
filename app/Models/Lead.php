<?php

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'name', 'company', 'phone', 'email', 'preferred_contact',
        'service_id', 'project_type', 'existing_url', 'budget', 'timeline',
        'message', 'required_features', 'attachment_path', 'source', 'referral_source',
        'status', 'priority', 'assigned_to', 'next_follow_up_at', 'last_contacted_at',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'required_features' => 'array',
            'status' => LeadStatus::class,
            'priority' => LeadPriority::class,
            'next_follow_up_at' => 'datetime',
            'last_contacted_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique, human-friendly lead reference (e.g. JS-2026-00042).
     */
    public static function generateReference(): string
    {
        do {
            $reference = sprintf('JS-%s-%s', now()->format('Y'), strtoupper(str()->random(6)));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            LeadStatus::Won, LeadStatus::Lost, LeadStatus::Spam, LeadStatus::Archived,
        ]);
    }

    public function scopeOverdueFollowUp(Builder $query): Builder
    {
        return $query->open()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now());
    }
}
