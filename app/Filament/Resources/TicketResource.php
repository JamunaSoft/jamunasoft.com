<?php

namespace App\Filament\Resources;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TicketResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Ticket::class;

    protected static string $permissionKey = 'tickets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|UnitEnum|null $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Ticket::awaitingStaff()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->copyable(),
                TextColumn::make('subject')->searchable()->limit(45),
                TextColumn::make('user.name')->label('Client')->searchable()->sortable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_reply_at')->since()->sortable(),
            ])
            ->defaultSort('last_reply_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(TicketStatus::class),
                SelectFilter::make('priority')->options(TicketPriority::class),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->url(fn (Ticket $record) => ViewTicket::getUrl(['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }
}
