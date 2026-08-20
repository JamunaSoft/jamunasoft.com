<?php

namespace App\Filament\Client\Resources\TicketResource\Pages;

use App\Enums\TicketPriority;
use App\Filament\Client\Resources\TicketResource;
use App\Services\TicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openTicket')
                ->label('Open ticket')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('subject')->required()->maxLength(150),
                    Select::make('priority')
                        ->options(TicketPriority::class)
                        ->default(TicketPriority::Normal)
                        ->required(),
                    Textarea::make('message')
                        ->rows(6)
                        ->required()
                        ->label('Describe your issue'),
                ])
                ->action(function (array $data) {
                    $priority = $data['priority'] instanceof TicketPriority
                        ? $data['priority']
                        : TicketPriority::from($data['priority']);

                    $ticket = app(TicketService::class)->open(auth()->user(), $data['subject'], $data['message'], $priority);

                    Notification::make()
                        ->title("Ticket {$ticket->reference} opened")
                        ->body('Our team will get back to you shortly.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
