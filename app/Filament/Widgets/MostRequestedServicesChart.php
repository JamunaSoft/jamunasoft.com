<?php

namespace App\Filament\Widgets;

use App\Models\Lead;
use App\Models\Service;
use Filament\Widgets\ChartWidget;

class MostRequestedServicesChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Most Requested Services';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('leads.view');
    }

    protected function getData(): array
    {
        $rows = Lead::query()
            ->selectRaw('service_id, COUNT(*) as total')
            ->whereNotNull('service_id')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'service_id');

        $names = Service::withTrashed()->whereIn('id', $rows->keys())->pluck('name', 'id');

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => $rows->values()->all(),
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $rows->keys()->map(fn ($id) => $names[$id] ?? 'Unknown')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
