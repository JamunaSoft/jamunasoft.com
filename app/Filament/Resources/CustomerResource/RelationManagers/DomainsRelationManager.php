<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\DomainResource;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
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
                TextColumn::make('renewal_price')->label('Custom renewal price')->money('BDT')->placeholder('Standard TLD pricing'),
                IconColumn::make('auto_renew')->boolean(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Renewal price')
                    ->schema([
                        TextInput::make('renewal_price')
                            ->label('Custom renewal price')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(99999999.99)
                            ->step(0.01)
                            ->prefix('BDT')
                            ->helperText('Annual price. Leave blank to use standard TLD pricing.'),
                    ]),
                Action::make('open')
                    ->label('Open')
                    ->url(fn (Domain $record) => DomainResource::getUrl('index')),
            ])
            ->paginated(false);
    }
}
