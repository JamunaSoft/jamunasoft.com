<?php

namespace App\Services;

use App\Enums\ExpenseCategory;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Carbon;

/**
 * Builds bank-statement-style data: an opening (carried-forward) balance,
 * date-ordered rows with running balance, and period totals.
 */
class StatementService
{
    /** Invoice statuses that count as money owed. */
    protected const BILLABLE = [InvoiceStatus::Unpaid, InvoiceStatus::Paid];

    /**
     * @return array{carryForward: float, rows: array<int, array<string, mixed>>, totalDebit: float, totalCredit: float, closing: float}
     */
    public function forClient(User $client, ?Carbon $from, ?Carbon $to): array
    {
        $to = ($to ?? now())->endOfDay();

        $carryForward = 0.0;

        if ($from !== null) {
            $carryForward = round(
                (float) Invoice::where('user_id', $client->id)
                    ->whereIn('status', self::BILLABLE)
                    ->where('created_at', '<', $from->startOfDay())
                    ->sum('total')
                - (float) Payment::where('user_id', $client->id)
                    ->where('paid_at', '<', $from->startOfDay())
                    ->sum('amount'),
                2,
            );
        }

        $rows = collect();

        Invoice::where('user_id', $client->id)
            ->whereIn('status', self::BILLABLE)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from->startOfDay()))
            ->where('created_at', '<=', $to)
            ->get()
            ->each(fn (Invoice $invoice) => $rows->push([
                'date' => $invoice->created_at,
                'description' => 'Invoice '.$invoice->reference,
                'debit' => (float) $invoice->total,
                'credit' => 0.0,
            ]));

        Payment::where('user_id', $client->id)
            ->when($from, fn ($query) => $query->where('paid_at', '>=', $from->startOfDay()))
            ->where('paid_at', '<=', $to)
            ->with('invoice')
            ->get()
            ->each(fn (Payment $payment) => $rows->push([
                'date' => $payment->paid_at,
                'description' => 'Payment'
                    .($payment->method ? ' — '.ucfirst($payment->method) : '')
                    .($payment->invoice ? ' against '.$payment->invoice->reference : ''),
                'debit' => 0.0,
                'credit' => (float) $payment->amount,
            ]));

        $rows = $rows->sortBy('date')->values();

        $running = $carryForward;
        $rows = $rows->map(function (array $row) use (&$running) {
            $running = round($running + $row['debit'] - $row['credit'], 2);
            $row['balance'] = $running;

            return $row;
        });

        return [
            'carryForward' => $carryForward,
            'rows' => $rows->all(),
            'totalDebit' => round($rows->sum('debit'), 2),
            'totalCredit' => round($rows->sum('credit'), 2),
            'closing' => $running,
        ];
    }

    /**
     * Vendor statement: payments made in the period, with the pre-system
     * previous balance carried forward (reduced only by "Previous due
     * payment" expenses).
     *
     * @return array{carryForward: float, rows: array<int, array<string, mixed>>, totalPaid: float, closing: float}
     */
    public function forVendor(Vendor $vendor, ?Carbon $from, ?Carbon $to): array
    {
        $to = ($to ?? now())->endOfDay();

        $carryForward = (float) $vendor->opening_balance;

        if ($from !== null) {
            $carryForward = round($carryForward - (float) $vendor->expenses()
                ->where('category', ExpenseCategory::PreviousDue)
                ->where('expensed_at', '<', $from->startOfDay())
                ->sum('amount'), 2);
        }

        $running = max(0, $carryForward);

        $rows = $vendor->expenses()
            ->when($from, fn ($query) => $query->where('expensed_at', '>=', $from->startOfDay()))
            ->where('expensed_at', '<=', $to)
            ->orderBy('expensed_at')
            ->orderBy('id')
            ->get()
            ->map(function ($expense) use (&$running) {
                if ($expense->category === ExpenseCategory::PreviousDue) {
                    $running = max(0, round($running - (float) $expense->amount, 2));
                }

                return [
                    'date' => $expense->expensed_at,
                    'description' => $expense->category->getLabel().' — '.$expense->description,
                    'paid' => (float) $expense->amount,
                    'previous_remaining' => $running,
                ];
            });

        return [
            'carryForward' => max(0, $carryForward),
            'rows' => $rows->all(),
            'totalPaid' => round($rows->sum('paid'), 2),
            'closing' => $running,
        ];
    }
}
