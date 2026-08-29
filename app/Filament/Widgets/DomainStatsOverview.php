<?php

namespace App\Filament\Widgets;

use App\Models\Domain;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DomainStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('domains.view');
    }

    protected function getStats(): array
    {
        $total = Domain::count();
        $active = Domain::whereIn('lifecycle_status', ['active', 'registered'])->count();
        $expiring = Domain::expiringWithin(30)->count();
        $customNameservers = Domain::where('nameserver_provider', 'custom')->count();

        return [
            Stat::make('Total domains', $total)
                ->description('All domains in the panel')
                ->color('info'),
            Stat::make('Active domains', $active)
                ->description($total > 0 ? round(($active / $total) * 100).'% of total domains' : 'No domains yet')
                ->color('success'),
            Stat::make('Expiring soon', $expiring)
                ->description('Within the next 30 days')
                ->color($expiring > 0 ? 'danger' : 'success'),
            Stat::make('Custom nameservers', $customNameservers)
                ->description('Domains using custom DNS')
                ->color('warning'),
        ];
    }
}
