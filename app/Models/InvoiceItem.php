<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'title', 'description', 'quantity', 'unit_price', 'total',
        'item_type', 'item_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * The bold line on the invoice. Items created before the title column
     * existed fall back to the first line of their description.
     */
    public function displayTitle(): string
    {
        if (filled($this->title)) {
            return $this->title;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim((string) $this->description)) ?: [];

        return $lines[0] ?? '';
    }

    /**
     * The muted detail lines under the title, or null when there are none.
     */
    public function displayDescription(): ?string
    {
        if (filled($this->title)) {
            return filled($this->description) ? trim((string) $this->description) : null;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim((string) $this->description)) ?: [];
        $rest = trim(implode("\n", array_slice($lines, 1)));

        return $rest !== '' ? $rest : null;
    }
}
