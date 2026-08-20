<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Reports\ReportsStatsOverview;
use App\Filament\Pages\Reports\RevenueChart;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class Reports extends Page
{
    protected string $view = 'filament.pages.reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->can('reports.view') || $user->can('reports.manage'));
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReportsStatsOverview::class,
            RevenueChart::class,
        ];
    }
}
