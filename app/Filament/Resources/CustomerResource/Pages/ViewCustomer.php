<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\User;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
            Action::make('statement')
                ->label('Statement')
                ->icon(Heroicon::OutlinedDocumentChartBar)
                ->color('gray')
                ->visible(fn (User $record) => ! $record->roles()->exists())
                ->modalDescription('Bank-statement-style PDF: invoices and payments with a running balance. Leave dates empty for the full history — with a from-date, the earlier balance is carried forward on the first row.')
                ->schema([
                    DatePicker::make('from')->label('From (optional)'),
                    DatePicker::make('to')->label('To (optional)'),
                ])
                ->modalSubmitActionLabel('Download statement')
                ->action(function (User $record, array $data) {
                    return redirect()->to(route('statements.client', array_filter([
                        'user' => $record->id,
                        'from' => $data['from'] ?? null,
                        'to' => $data['to'] ?? null,
                    ])));
                }),
            Action::make('addPreviousBalance')
                ->label('Add previous balance')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('gray')
                ->visible(fn (User $record) => ! $record->roles()->exists())
                ->modalDescription('For dues from before this system: creates an unpaid "Previous balance" invoice (not emailed) so payments and reminders track it normally.')
                ->schema([
                    TextInput::make('amount')
                        ->numeric()
                        ->prefix('৳')
                        ->required()
                        ->minValue(1),
                    TextInput::make('note')
                        ->placeholder('Dues up to Aug 2026')
                        ->helperText('Shown under the item title on the invoice.'),
                ])
                ->action(function (User $record, array $data) {
                    $invoice = app(InvoiceService::class)->create(
                        userId: $record->id,
                        items: [[
                            'title' => 'Previous balance',
                            'description' => $data['note'] ?: 'Dues carried over from before '.now()->format('M Y'),
                            'unit_price' => (float) $data['amount'],
                        ]],
                        sendEmail: false,
                    );

                    Notification::make()
                        ->title("Invoice {$invoice->reference} created for the previous balance")
                        ->body('Review it on the Invoices tab, then email it if needed.')
                        ->success()
                        ->send();
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
