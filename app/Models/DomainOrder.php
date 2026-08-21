<?php

namespace App\Models;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DomainOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'user_id', 'customer_name', 'customer_email', 'customer_phone',
        'domain_name', 'registrar', 'type', 'years', 'amount', 'currency', 'status',
        'payment_method', 'payment_reference', 'spaceship_operation_id',
        'error_message', 'paid_at', 'completed_at', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'type' => DomainOrderType::class,
            'status' => DomainOrderStatus::class,
            'years' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = sprintf('DOM-%s-%s', now()->format('Y'), strtoupper(str()->random(6)));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [
            DomainOrderStatus::PendingPayment,
            DomainOrderStatus::Paid,
            DomainOrderStatus::Processing,
        ]);
    }
}
