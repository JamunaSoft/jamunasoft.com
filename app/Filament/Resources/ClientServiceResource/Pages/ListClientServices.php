<?php

namespace App\Filament\Resources\ClientServiceResource\Pages;

use App\Filament\Resources\ClientServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientServices extends ListRecords
{
    protected static string $resource = ClientServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
