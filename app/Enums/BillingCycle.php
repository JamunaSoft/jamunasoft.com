<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\Carbon;

enum BillingCycle: string implements HasLabel
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnually = 'semi_annually';
    case Yearly = 'yearly';
    case Biennially = 'biennially';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::SemiAnnually => 'Semi-annually',
            self::Yearly => 'Yearly',
            self::Biennially => 'Every 2 years',
        };
    }

    public function advance(Carbon $date): Carbon
    {
        return match ($this) {
            self::Monthly => $date->copy()->addMonthNoOverflow(),
            self::Quarterly => $date->copy()->addMonthsNoOverflow(3),
            self::SemiAnnually => $date->copy()->addMonthsNoOverflow(6),
            self::Yearly => $date->copy()->addYearNoOverflow(),
            self::Biennially => $date->copy()->addYearsNoOverflow(2),
        };
    }
}
