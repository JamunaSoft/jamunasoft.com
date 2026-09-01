<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ExpenseCategory: string implements HasLabel
{
    case ServerHosting = 'server_hosting';
    case DomainCosts = 'domain_costs';
    case Outsourcing = 'outsourcing';
    case Salary = 'salary';
    case Rent = 'rent';
    case Marketing = 'marketing';
    case Software = 'software';
    case Utilities = 'utilities';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::ServerHosting => 'Server / Hosting',
            self::DomainCosts => 'Domain costs',
            self::Outsourcing => 'Outsourcing / Production',
            self::Salary => 'Salary',
            self::Rent => 'Office rent',
            self::Marketing => 'Marketing / Boosting',
            self::Software => 'Software subscriptions',
            self::Utilities => 'Utilities',
            self::Other => 'Other',
        };
    }
}
