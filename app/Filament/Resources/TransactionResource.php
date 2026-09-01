<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Read-only ledger of every payment received, across all invoices.
 */
class TransactionResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Payment::class;

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'invoice']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')->label('Date')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Client')->searchable()->sortable(),
                TextColumn::make('invoice.reference')
                    ->label('Invoice')
                    ->searchable()
                    ->url(fn (Payment $record) => $record->invoice
                        ? InvoiceResource::getUrl('index', ['tableSearch' => $record->invoice->reference])
                        : null),
                TextColumn::make('method')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'bkash' => 'bKash',
                        'nagad' => 'Nagad',
                        'rocket' => 'Rocket',
                        'bank' => 'Bank Transfer',
                        default => ucfirst((string) $state) ?: '—',
                    }),
                TextColumn::make('transaction_id')->label('Reference')->placeholder('—')->searchable()->toggleable(),
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable()
                    ->summarize(Sum::make()->money('BDT')->label('Total')),
            ])
            ->defaultSort('paid_at', 'desc')
            ->filters([
                SelectFilter::make('method')->options([
                    'bkash' => 'bKash',
                    'nagad' => 'Nagad',
                    'rocket' => 'Rocket',
                    'bank' => 'Bank transfer',
                    'cash' => 'Cash',
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
                            $query->whereYear('paid_at', substr($data['value'], 0, 4))
                                ->whereMonth('paid_at', substr($data['value'], 5, 2));
                        }
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
