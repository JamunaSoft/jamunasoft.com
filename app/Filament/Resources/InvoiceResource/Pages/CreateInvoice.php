<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['reference'] = Invoice::generateReference();

        return $data;
    }

    /**
     * No auto-email on manual creation: the admin reviews the invoice first,
     * then sends it with the "Email to client" action. (System-generated
     * invoices — renewals, domain orders — still email automatically.)
     */
    protected function afterCreate(): void
    {
        /** @var Invoice $invoice */
        $invoice = $this->record;

        app(InvoiceService::class)->recalculateTotals($invoice);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Invoice created')
            ->body('Not emailed yet — review it, then use "Email to client" to send it.');
    }
}
