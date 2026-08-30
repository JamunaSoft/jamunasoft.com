<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'user_id', 'status', 'currency', 'subtotal', 'discount',
        'total', 'amount_paid', 'due_at', 'paid_at', 'last_reminded_at',
        'notes', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = sprintf('INV-%s-%s', now()->format('Y'), strtoupper(str()->random(6)));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany(EmailLog::class, 'related');
    }

    public function balance(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Unpaid
            && $this->due_at !== null
            && $this->due_at->isPast()
            && ! $this->due_at->isToday();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Draft, InvoiceStatus::Unpaid]);
    }

    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Unpaid);
    }
}
