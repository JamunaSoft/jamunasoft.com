<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmailLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'emailLogs';

    protected static ?string $title = 'Emails';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                TextColumn::make('type')
                    ->label('Event')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title())
                    ->color('gray'),
                TextColumn::make('subject')->searchable()->limit(55),
                TextColumn::make('recipient')->label('To')->copyable(),
                TextColumn::make('bcc')->label('BCC')->toggleable(),
                TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'queued' ? 'warning' : ($state === 'sent' ? 'success' : 'danger')),
                TextColumn::make('queued_at')->label('Queued')->dateTime()->sortable(),
            ])
            ->defaultSort('queued_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
