<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum LeadActivityType: string implements HasLabel
{
    case Note = 'note';
    case StatusChange = 'status_change';
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case QuotationSent = 'quotation_sent';
    case FollowUp = 'follow_up';

    public function getLabel(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::StatusChange => 'Status Change',
            self::Call => 'Call',
            self::Email => 'Email',
            self::Meeting => 'Meeting',
            self::QuotationSent => 'Quotation Sent',
            self::FollowUp => 'Follow-up Scheduled',
        };
    }
}
