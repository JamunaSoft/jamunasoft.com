<?php

namespace App\Filament\Resources\VendorResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The vendor's ledger: every payment made to them, newest first.
 */
class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $title = 'Payments to this vendor';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                TextColumn::make('expensed_at')->label('Date')->date()->sortable(),
                TextColumn::make('category')->badge()->color('gray'),
                TextColumn::make('description')->searchable()->limit(60),
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
                TextColumn::make('amount')
                    ->money('BDT')
                    ->sortable()
                    ->summarize(Sum::make()->money('BDT')->label('Total')),
            ])
            ->defaultSort('expensed_at', 'desc');
    }
}
