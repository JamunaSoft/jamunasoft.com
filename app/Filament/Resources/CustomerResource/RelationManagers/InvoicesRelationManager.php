<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('total')->money('BDT'),
                TextColumn::make('amount_paid')->money('BDT')->label('Paid'),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_at')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
