<?php

namespace App\Filament\Resources\DomainResource\Pages;

use App\Filament\Resources\DomainResource;
use App\Filament\Widgets\DomainStatsOverview;
use App\Services\Registrars\RegistrarException;
use App\Services\Registrars\RegistrarManager;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListDomains extends ListRecords
{
    protected static string $resource = DomainResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            DomainStatsOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync from registrars')
                ->icon(Heroicon::OutlinedArrowPath)
                ->action(function (RegistrarManager $registrars) {
                    $lines = [];
                    $failed = false;

                    foreach (RegistrarManager::PROVIDERS as $key => $label) {
                        if (! $this->isConfigured($key)) {
                            continue;
                        }

                        try {
                            $result = $registrars->for($key)->syncAll();
                        } catch (RegistrarException $e) {
                            $lines[] = "{$label}: failed — {$e->getMessage()}";
                            $failed = true;

                            continue;
                        }

                        $line = "{$label}: {$result['synced']} synced ({$result['created']} new)";

                        if ($result['missing'] !== []) {
                            $line .= ' — no longer there: '.implode(', ', $result['missing']);
                        }

                        $lines[] = $line;
                    }

                    Notification::make()
                        ->title($failed ? 'Sync finished with problems' : 'Domains synced')
                        ->body(implode("\n", $lines))
                        ->{$failed ? 'warning' : 'success'}()
                        ->persistent()
                        ->send();
                }),
        ];
    }

    protected function isConfigured(string $key): bool
    {
        return match ($key) {
            'spaceship' => filled(config('services.spaceship.key')),
            'resellcube' => filled(config('services.resellcube.user_id')),
            default => false,
        };
    }
}
