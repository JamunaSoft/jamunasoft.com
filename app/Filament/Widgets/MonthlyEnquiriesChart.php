<?php

namespace App\Filament\Widgets;

use App\Models\ContactMessage;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class MonthlyEnquiriesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Enquiries (last 6 months)';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))
            ->map(fn (int $back) => now()->subMonths($back)->startOfMonth());

        $countFor = function (string $model, Carbon $month): int {
            return $model::query()
                ->whereBetween('created_at', [$month, $month->copy()->endOfMonth()])
                ->count();
        };

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $months->map(fn (Carbon $m) => $countFor(Lead::class, $m))->all(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, .15)',
                    'fill' => true,
                ],
                [
                    'label' => 'Contact messages',
                    'data' => $months->map(fn (Carbon $m) => $countFor(ContactMessage::class, $m))->all(),
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => 'rgba(6, 182, 212, .15)',
                    'fill' => true,
                ],
            ],
            'labels' => $months->map(fn (Carbon $m) => $m->format('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
