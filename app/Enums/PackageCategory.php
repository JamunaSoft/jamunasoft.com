<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PackageCategory: string implements HasLabel
{
    case Website = 'website';
    case Ecommerce = 'ecommerce';
    case Software = 'software';
    case Maintenance = 'maintenance';
    case Marketing = 'marketing';
    case SocialMedia = 'social_media';
    case Hosting = 'hosting';

    public function getLabel(): string
    {
        return match ($this) {
            self::Website => 'Website',
            self::Ecommerce => 'E-commerce',
            self::Software => 'Custom Software',
            self::Maintenance => 'Maintenance',
            self::Marketing => 'Digital Marketing',
            self::SocialMedia => 'Social Media',
            self::Hosting => 'Hosting & Email',
        };
    }
}
