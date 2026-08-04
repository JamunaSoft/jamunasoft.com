<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PageTemplate: string implements HasLabel
{
    case Standard = 'standard';
    case Landing = 'landing';
    case Policy = 'policy';

    public function getLabel(): string
    {
        return match ($this) {
            self::Standard => 'Standard Content Page',
            self::Landing => 'Landing Page',
            self::Policy => 'Policy Page',
        };
    }
}
