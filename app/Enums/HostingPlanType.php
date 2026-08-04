<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum HostingPlanType: string implements HasLabel
{
    case Shared = 'shared';
    case Managed = 'managed';
    case Vps = 'vps';
    case Cloud = 'cloud';
    case Email = 'email';

    public function getLabel(): string
    {
        return match ($this) {
            self::Shared => 'Shared Hosting',
            self::Managed => 'Managed Hosting',
            self::Vps => 'VPS',
            self::Cloud => 'Cloud Server',
            self::Email => 'Business Email',
        };
    }
}
