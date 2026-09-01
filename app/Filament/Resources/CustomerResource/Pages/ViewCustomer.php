<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loginAsClient')
                ->label('Login as client')
                ->icon(Heroicon::OutlinedArrowRightEndOnRectangle)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Open the client panel as this client. A banner with a "Return to admin" link will be shown until you switch back.')
                ->visible(fn (User $record) => ! $record->roles()->exists())
                ->action(function (User $record) {
                    session(['impersonator_id' => auth()->id()]);
                    auth()->login($record);

                    // AuthenticateSession compares this hash on every request;
                    // without updating it the panel logs the new user out.
                    session(['password_hash_web' => $record->getAuthPassword()]);

                    $this->redirect('/client');
                }),
            Action::make('sendPasswordLink')
                ->label('Send password link')
                ->icon(Heroicon::OutlinedKey)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Email the client a link to set a new password for the client panel.')
                ->visible(fn (User $record) => ! $record->roles()->exists())
                ->action(function (User $record) {
                    CustomerResource::sendSetPasswordLink($record);

                    Notification::make()
                        ->title("Password link emailed to {$record->email}")
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
