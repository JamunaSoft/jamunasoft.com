<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\DomainResource;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('lifecycle_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => DomainResource::statusColor($state)),
                TextColumn::make('expires_at')->date()->sortable(),
                IconColumn::make('auto_renew')->boolean(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (Domain $record) => DomainResource::getUrl('index')),
            ])
            ->paginated(false);
    }
}
