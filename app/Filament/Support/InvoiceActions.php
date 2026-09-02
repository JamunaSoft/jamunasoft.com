<?php

namespace App\Filament\Support;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Invoice row/bulk actions shared between the Invoices list and the
 * client profile's Invoices tab.
 */
class InvoiceActions
{
    /** @return array<int, Action> */
    public static function recordActions(): array
    {
        return [
            Action::make('pdf')
                ->label('PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->url(fn (Invoice $record) => route('invoices.pdf', ['invoice' => $record, 'download' => 1])),
            Action::make('recordPayment')
                ->label('Record payment')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid)
                ->schema(fn (Invoice $record) => [
                    TextInput::make('amount')
                        ->numeric()
                        ->prefix('৳')
                        ->default($record->balance())
                        ->required(),
                    Select::make('method')
                        ->options([
                            'bkash' => 'bKash',
                            'nagad' => 'Nagad',
                            'rocket' => 'Rocket',
                            'bank' => 'Bank transfer',
                            'cash' => 'Cash',
                            'other' => 'Other',
                        ])
                        ->required(),
                    TextInput::make('transaction_id')->label('Transaction ID / reference'),
                ])
                ->action(function (Invoice $record, array $data) {
                    app(InvoiceService::class)->recordPayment(
                        $record,
                        (float) $data['amount'],
                        $data['method'],
                        $data['transaction_id'] ?? null,
                        recordedBy: auth()->id(),
                    );

                    Notification::make()
                        ->title($record->refresh()->status === InvoiceStatus::Paid
                            ? 'Payment recorded — invoice paid in full'
                            : 'Partial payment recorded')
                        ->success()
                        ->send();
                }),
            Action::make('send')
                ->label('Email to client')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid)
                ->requiresConfirmation()
                ->action(function (Invoice $record) {
                    app(InvoiceService::class)->sendInvoice($record);

                    Notification::make()->title('Invoice emailed to '.$record->user->email)->success()->send();
                }),
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->color('success')
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid && $record->whatsappUrl() !== null)
                ->url(fn (Invoice $record) => $record->whatsappUrl())
                ->openUrlInNewTab(),
            Action::make('remind')
                ->label('Send reminder')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('warning')
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid)
                ->requiresConfirmation()
                ->modalDescription(fn (Invoice $record) => $record->last_reminded_at
                    ? 'Last reminder was sent '.$record->last_reminded_at->diffForHumans().'. Send another one now?'
                    : 'No reminder has been sent for this invoice yet. Send one now?')
                ->action(function (Invoice $record) {
                    app(InvoiceService::class)->sendReminder($record);

                    Notification::make()
                        ->title('Reminder emailed to '.implode(', ', $record->user->billingEmails()))
                        ->success()
                        ->send();
                }),
            Action::make('duplicate')
                ->label('Duplicate')
                ->icon(Heroicon::OutlinedDocumentDuplicate)
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Copy this invoice into a fresh unpaid one with the same items. It is not emailed — you can review and edit it first.')
                ->action(function (Invoice $record) {
                    $copy = app(InvoiceService::class)->duplicate($record);

                    Notification::make()
                        ->title("Invoice {$copy->reference} created")
                        ->body('Review it, then use "Email to client" to send it.')
                        ->success()
                        ->send();

                    return redirect(InvoiceResource::getUrl('edit', ['record' => $copy]));
                }),
            DeleteAction::make()
                ->visible(fn (Invoice $record) => $record->payments()->doesntExist())
                ->modalDescription('Remove this invoice permanently. Only invoices without any recorded payment can be deleted — cancel instead if money ever touched it.'),
            Action::make('cancel')
                ->label('Cancel')
                ->icon(Heroicon::OutlinedXMark)
                ->color('danger')
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid && (float) $record->amount_paid === 0.0)
                ->requiresConfirmation()
                ->action(function (Invoice $record) {
                    $record->update(['status' => InvoiceStatus::Cancelled]);

                    Notification::make()->title('Invoice cancelled')->success()->send();
                }),
        ];
    }

    public static function emailBundleBulkAction(): BulkAction
    {
        return BulkAction::make('emailTogether')
            ->label('Email together')
            ->icon(Heroicon::OutlinedEnvelopeOpen)
            ->requiresConfirmation()
            ->modalHeading('Email invoices together')
            ->modalDescription('Send the selected unpaid invoices of one client in a SINGLE email — every PDF attached, each with its own pay link.')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                $records = $records->values();

                if ($records->pluck('user_id')->unique()->count() > 1) {
                    Notification::make()->title('Only invoices of the same client can be emailed together.')->danger()->send();

                    return;
                }

                if ($records->contains(fn (Invoice $invoice) => $invoice->status !== InvoiceStatus::Unpaid)) {
                    Notification::make()->title('Only unpaid invoices can be emailed together.')->danger()->send();

                    return;
                }

                app(InvoiceService::class)->sendBundle($records);

                Notification::make()
                    ->title($records->count().' invoices emailed in one message')
                    ->body('Sent to '.implode(', ', $records->first()->recipients()).' with all PDFs attached.')
                    ->success()
                    ->send();
            });
    }

    public static function mergeBulkAction(): BulkAction
    {
        return BulkAction::make('merge')
            ->label('Merge invoices')
            ->icon(Heroicon::OutlinedArrowsPointingIn)
            ->requiresConfirmation()
            ->modalHeading('Merge invoices')
            ->modalDescription('All items and payments of the selected unpaid invoices move into the oldest one; the emptied invoices are cancelled. No email is sent — review the merged invoice, then use "Email to client".')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) {
                try {
                    $target = app(InvoiceService::class)->merge($records);

                    Notification::make()
                        ->title("Merged into {$target->reference}")
                        ->body('Review it, then use "Email to client" to send it.')
                        ->success()
                        ->send();
                } catch (\InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
