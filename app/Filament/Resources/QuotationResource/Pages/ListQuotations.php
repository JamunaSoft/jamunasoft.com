<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\Quotation;
use App\Services\QuotationService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotations extends ListRecords
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $data['reference'] = Quotation::generateReference();
                    $data['token'] = str()->random(40);

                    return $data;
                })
                ->after(fn (Quotation $record) => app(QuotationService::class)->recalculateTotals($record)),
        ];
    }
}
