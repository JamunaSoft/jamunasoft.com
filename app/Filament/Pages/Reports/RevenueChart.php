<?php

namespace App\Filament\Pages\Reports;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue — last 12 months';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow(11);

        // Group in PHP to stay database-agnostic.
        $byMonth = Payment::query()
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))
            ->map(fn ($payments) => (float) $payments->sum('amount'));

        $labels = [];
        $values = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $labels[] = $month->format('M y');
            $values[] = $byMonth->get($month->format('Y-m'), 0.0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Payments received (৳)',
                    'data' => $values,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
