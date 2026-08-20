<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('billing_cycle')->badge()->color('gray'),
                TextColumn::make('price')->money('BDT'),
                TextColumn::make('next_due_at')->label('Next due')->date()->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->paginated(false);
    }
}
