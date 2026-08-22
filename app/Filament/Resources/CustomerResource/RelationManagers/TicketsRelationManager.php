<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Ticket;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('subject')
            ->columns([
                TextColumn::make('reference'),
                TextColumn::make('subject')->limit(40),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_reply_at')->since()->sortable(),
            ])
            ->defaultSort('last_reply_at', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->url(fn (Ticket $record) => ViewTicket::getUrl(['record' => $record])),
            ]);
    }
}
