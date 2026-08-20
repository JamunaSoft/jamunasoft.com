<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TicketStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case Answered = 'answered';
    case CustomerReply = 'customer_reply';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Answered => 'Answered',
            self::CustomerReply => 'Customer Reply',
            self::Closed => 'Closed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'warning',
            self::Answered => 'success',
            self::CustomerReply => 'info',
            self::Closed => 'gray',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }
}
