<?php

namespace App\Filament\Pages\Reports;

use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Income vs Expenses — last 12 months';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->startOfMonth()->subMonthsNoOverflow(11);

        // Group in PHP to stay database-agnostic.
        $incomeByMonth = Payment::query()
            ->where('paid_at', '>=', $start)
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment) => $payment->paid_at->format('Y-m'))
            ->map(fn ($payments) => (float) $payments->sum('amount'));

        $expensesByMonth = Expense::query()
            ->where('expensed_at', '>=', $start)
            ->get(['amount', 'expensed_at'])
            ->groupBy(fn (Expense $expense) => $expense->expensed_at->format('Y-m'))
            ->map(fn ($expenses) => (float) $expenses->sum('amount'));

        $labels = [];
        $income = [];
        $expenses = [];

        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonthsNoOverflow($i);
            $labels[] = $month->format('M y');
            $income[] = $incomeByMonth->get($month->format('Y-m'), 0.0);
            $expenses[] = $expensesByMonth->get($month->format('Y-m'), 0.0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Income (৳)',
                    'data' => $income,
                    'backgroundColor' => '#1E9E58',
                ],
                [
                    'label' => 'Expenses (৳)',
                    'data' => $expenses,
                    'backgroundColor' => '#C43D3D',
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
