<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Enums\TicketStatus;
use App\Filament\Resources\TicketResource;
use App\Services\TicketService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ViewTicket extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TicketResource::class;

    protected string $view = 'filament.tickets.thread';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getHeading(): string
    {
        return $this->record->reference.' — '.$this->record->subject;
    }

    public function getSubheading(): ?string
    {
        return $this->record->user->name.' · '.$this->record->priority->getLabel().' priority · '.$this->record->status->getLabel();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reply')
                ->label('Reply')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->schema([
                    Textarea::make('message')->rows(5)->required()->label('Your reply'),
                ])
                ->action(function (array $data) {
                    app(TicketService::class)->reply($this->record, auth()->user(), $data['message'], isStaff: true);
                    $this->record->refresh();

                    Notification::make()->title('Reply sent — the client has been emailed')->success()->send();
                }),
            Action::make('close')
                ->label('Close ticket')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('gray')
                ->visible(fn () => $this->record->status !== TicketStatus::Closed)
                ->requiresConfirmation()
                ->action(function () {
                    app(TicketService::class)->close($this->record);
                    $this->record->refresh();

                    Notification::make()->title('Ticket closed')->success()->send();
                }),
        ];
    }
}
