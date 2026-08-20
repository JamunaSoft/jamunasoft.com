<?php

namespace App\Filament\Client\Resources;

use App\Enums\TicketStatus;
use App\Filament\Client\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Client\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static ?string $navigationLabel = 'Support Tickets';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', TicketStatus::Answered)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference'),
                TextColumn::make('subject')->limit(45),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_reply_at')->since()->sortable(),
            ])
            ->defaultSort('last_reply_at', 'desc')
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (Ticket $record) => ViewTicket::getUrl(['record' => $record])),
            ])
            ->emptyStateHeading('No tickets yet')
            ->emptyStateDescription('Need help? Open a ticket and our team will get back to you.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }
}
