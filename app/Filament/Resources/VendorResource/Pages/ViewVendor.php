<?php

namespace App\Filament\Resources\VendorResource\Pages;

use App\Filament\Resources\VendorResource;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewVendor extends ViewRecord
{
    protected static string $resource = VendorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('statement')
                ->label('Statement')
                ->icon(Heroicon::OutlinedDocumentChartBar)
                ->color('gray')
                ->modalDescription('PDF of every payment made to this vendor, with the previous balance carried forward. Leave dates empty for the full history.')
                ->schema([
                    DatePicker::make('from')->label('From (optional)'),
                    DatePicker::make('to')->label('To (optional)'),
                ])
                ->modalSubmitActionLabel('Download statement')
                ->action(function (Vendor $record, array $data) {
                    return redirect()->to(route('statements.vendor', array_filter([
                        'vendor' => $record->id,
                        'from' => $data['from'] ?? null,
                        'to' => $data['to'] ?? null,
                    ])));
                }),
            EditAction::make(),
        ];
    }
}
