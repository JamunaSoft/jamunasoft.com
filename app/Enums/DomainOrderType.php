<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DomainOrderType: string implements HasLabel
{
    case Register = 'register';
    case Renew = 'renew';
    case Transfer = 'transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Register => 'Registration',
            self::Renew => 'Renewal',
            self::Transfer => 'Transfer',
        };
    }
}
