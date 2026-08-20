<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientService extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'hosting_plan_id', 'name', 'domain', 'billing_cycle',
        'price', 'status', 'next_due_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'status' => ClientServiceStatus::class,
            'price' => 'decimal:2',
            'next_due_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hostingPlan(): BelongsTo
    {
        return $this->belongsTo(HostingPlan::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientServiceStatus::Active);
    }

    /**
     * An open (draft/unpaid) invoice already covers this service's next period.
     */
    public function hasOpenInvoice(): bool
    {
        return InvoiceItem::query()
            ->where('item_type', 'client_service')
            ->where('item_id', $this->id)
            ->whereHas('invoice', fn (Builder $query) => $query->open())
            ->exists();
    }
}
