<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\LedgerEntry;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Money-in / money-out ledger: every payment received and every expense,
 * in one bank-statement-style view (backed by the ledger_entries SQL view).
 */
class TransactionResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = LedgerEntry::class;

    protected static string $permissionKey = 'billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Transactions';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?int $navigationSort = 6;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('happened_at')->label('Date')->dateTime('d M Y')->sortable(),
                TextColumn::make('direction')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'in' ? 'Received' : 'Expense')
                    ->color(fn (string $state) => $state === 'in' ? 'success' : 'danger'),
                TextColumn::make('counterparty')
                    ->label('Client / Vendor')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('details')
                    ->state(fn (LedgerEntry $record) => $record->direction === 'in'
                        ? ($record->invoice_reference ? 'Invoice '.$record->invoice_reference : '—')
                        : trim(($record->category?->getLabel() ?? '').' — '.$record->description, ' —'))
                    ->limit(45),
                TextColumn::make('method')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                        'bank' => 'Bank Transfer',
                        default => ucfirst((string) $state) ?: '—',
                    })
                    ->placeholder('—'),
                TextColumn::make('reference')->label('Trx ID')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('signed_amount')
                    ->label('Amount')
                    ->state(fn (LedgerEntry $record) => ($record->direction === 'in' ? '+' : '−').'৳'.number_format((float) $record->amount, 2))
                    ->color(fn (LedgerEntry $record) => $record->direction === 'in' ? 'success' : 'danger')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->summarize(Sum::make()->money('BDT')->label('Net')),
            ])
            ->defaultSort('happened_at', 'desc')
            ->filters([
                SelectFilter::make('direction')
                    ->label('Type')
                    ->options(['in' => 'Received', 'out' => 'Expense']),
                SelectFilter::make('method')->options([
                    'bkash' => 'bKash',
                    'nagad' => 'Nagad',
                    'rocket' => 'Rocket',
                    'bank' => 'Bank transfer',
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'other' => 'Other',
                ]),
                SelectFilter::make('month')
                    ->options(fn () => collect(range(0, 11))
                        ->mapWithKeys(fn (int $i) => [
                            now()->subMonthsNoOverflow($i)->format('Y-m') => now()->subMonthsNoOverflow($i)->format('F Y'),
                        ])
                        ->all())
                    ->query(function ($query, array $data) {
                        if (filled($data['value'])) {
                            $query->whereYear('happened_at', substr($data['value'], 0, 4))
                                ->whereMonth('happened_at', substr($data['value'], 5, 2));
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editPayment')
                    ->label('Edit payment')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (LedgerEntry $record) => $record->direction === 'in' && str_starts_with($record->id, 'P'))
                    ->fillForm(function (LedgerEntry $record): array {
                        $payment = Payment::findOrFail((int) substr($record->id, 1));

                        return [
                            'amount' => $payment->amount,
                            'paid_at' => $payment->paid_at,
                            'method' => $payment->method,
                            'transaction_id' => $payment->transaction_id,
                        ];
                    })
                    ->schema([
                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('৳')
                            ->required(),
                        DatePicker::make('paid_at')
                            ->label('Payment date')
                            ->required(),
                        Select::make('method')
                            ->options([
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'rocket' => 'Rocket',
                                'bank' => 'Bank transfer',
                                'cash' => 'Cash',
                                'card' => 'Card',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('transaction_id')->label('Transaction ID / reference'),
                    ])
                    ->action(function (LedgerEntry $record, array $data): void {
                        DB::transaction(function () use ($record, $data): void {
                            $payment = Payment::with('invoice')
                                ->lockForUpdate()
                                ->findOrFail((int) substr($record->id, 1));

                            $payment->update([
                                'amount' => (float) $data['amount'],
                                'paid_at' => Carbon::parse($data['paid_at']),
                                'method' => $data['method'],
                                'transaction_id' => $data['transaction_id'] ?? null,
                            ]);

                            $invoice = $payment->invoice()->lockForUpdate()->firstOrFail();
                            $amountPaid = round((float) $invoice->payments()->sum('amount'), 2);
                            $updates = ['amount_paid' => $amountPaid];

                            if ($amountPaid >= (float) $invoice->total) {
                                $updates['status'] = InvoiceStatus::Paid;
                                $updates['paid_at'] = $invoice->payments()->latest('paid_at')->value('paid_at');
                            } elseif ($invoice->status === InvoiceStatus::Paid) {
                                $updates['status'] = InvoiceStatus::Unpaid;
                                $updates['paid_at'] = null;
                            }

                            $invoice->update($updates);
                        });

                        Notification::make()
                            ->title('Payment updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransactions::route('/'),
        ];
    }
}
