<?php

namespace App\Filament\Widgets;

use App\Enums\LeadStatus;
use App\Models\Lead;
use Filament\Widgets\ChartWidget;

class LeadStatusChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Leads by Status';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    protected function getData(): array
    {
        $counts = Lead::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];

        foreach (LeadStatus::cases() as $status) {
            $labels[] = $status->getLabel();
            $data[] = (int) ($counts[$status->value] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $data,
                    'backgroundColor' => [
                        '#3b82f6', '#6366f1', '#f59e0b', '#f97316', '#eab308',
                        '#22c55e', '#ef4444', '#9ca3af', '#64748b',
                    ],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
