<?php

namespace App\Models;

use App\Enums\QuotationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'token', 'lead_id', 'user_id', 'invoice_id',
        'customer_name', 'customer_email', 'status', 'valid_until',
        'subtotal', 'discount', 'total', 'notes', 'sent_at', 'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = sprintf('QT-%s-%s', now()->format('Y'), strtoupper(str()->random(6)));
        } while (static::withTrashed()->where('reference', $reference)->exists());

        return $reference;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function isExpired(): bool
    {
        return $this->status === QuotationStatus::Sent
            && $this->valid_until !== null
            && $this->valid_until->isPast()
            && ! $this->valid_until->isToday();
    }

    public function publicUrl(): string
    {
        return route('quotation.show', ['reference' => $this->reference, 'token' => $this->token]);
    }
}
