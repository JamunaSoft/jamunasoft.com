<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'domainOrders';

    protected static ?string $title = 'Domain Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('domain_name'),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('amount')->money('BDT'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
