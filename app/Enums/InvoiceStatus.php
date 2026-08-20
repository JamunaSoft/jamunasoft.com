<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum InvoiceStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Unpaid = 'unpaid';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Unpaid => 'Unpaid',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Unpaid => 'warning',
            self::Paid => 'success',
            self::Cancelled => 'gray',
            self::Refunded => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Draft, self::Unpaid], true);
    }
}
