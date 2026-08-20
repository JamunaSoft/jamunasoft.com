<?php

namespace App\Filament\Resources\DomainResource\Pages;

use App\Filament\Resources\DomainResource;
use App\Services\Spaceship\DomainSyncService;
use App\Services\Spaceship\SpaceshipException;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync from Spaceship')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (DomainSyncService $sync) {
                    try {
                        $result = $sync->sync();
                    } catch (SpaceshipException $e) {
                        Notification::make()
                            ->title('Spaceship sync failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title("Synced {$result['synced']} domains ({$result['created']} new)")
                        ->body($result['missing'] !== []
                            ? 'No longer at Spaceship: '.implode(', ', $result['missing'])
                            : null)
                        ->success()
                        ->send();
                }),
        ];
    }
}
