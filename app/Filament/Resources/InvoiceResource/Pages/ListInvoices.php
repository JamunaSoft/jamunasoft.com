<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['reference'] = Invoice::generateReference();

                    return $data;
                })
                ->after(function (Invoice $record) {
                    app(InvoiceService::class)->recalculateTotals($record);
                    app(InvoiceService::class)->sendInvoice($record);
                }),
        ];
    }
}
