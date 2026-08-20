<?php

namespace App\Filament\Pages\Reports;

use App\Models\ClientService;
use App\Models\Domain;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ReportsStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $thisMonth = (float) Payment::whereBetween('paid_at', [now()->startOfMonth(), now()])->sum('amount');
        $lastMonth = (float) Payment::whereBetween('paid_at', [
            now()->subMonthNoOverflow()->startOfMonth(),
            now()->subMonthNoOverflow()->endOfMonth(),
        ])->sum('amount');

        $outstanding = (float) Invoice::unpaid()->sum(DB::raw('total - amount_paid'));
        $overdueCount = Invoice::unpaid()->whereDate('due_at', '<', now())->count();

        $activeServices = ClientService::active()->count();
        $servicesDueSoon = ClientService::active()
            ->whereNotNull('next_due_at')
            ->whereBetween('next_due_at', [now(), now()->addDays(30)])
            ->count();

        $domainCount = Domain::count();
        $domainsExpiring = Domain::expiringWithin(30)->count();

        return [
            Stat::make('Revenue this month', '৳'.number_format($thisMonth, 0))
                ->description('Last month: ৳'.number_format($lastMonth, 0))
                ->color($thisMonth >= $lastMonth ? 'success' : 'warning'),
            Stat::make('Outstanding', '৳'.number_format($outstanding, 0))
                ->description($overdueCount.' overdue '.str('invoice')->plural($overdueCount))
                ->color($overdueCount > 0 ? 'danger' : 'gray'),
            Stat::make('Active services', (string) $activeServices)
                ->description($servicesDueSoon.' due within 30 days')
                ->color('info'),
            Stat::make('Domains', (string) $domainCount)
                ->description($domainsExpiring.' expiring within 30 days')
                ->color($domainsExpiring > 0 ? 'warning' : 'success'),
            Stat::make('Open tickets', (string) Ticket::awaitingStaff()->count())
                ->color('info'),
        ];
    }
}
