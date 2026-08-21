<?php

namespace App\Console\Commands;

use App\Services\Registrars\RegistrarException;
use App\Services\Registrars\RegistrarManager;
use Illuminate\Console\Command;

class SyncRegistrarDomains extends Command
{
    protected $signature = 'domains:sync';

    protected $description = 'Sync domains from every configured registrar into the local database';

    public function handle(RegistrarManager $registrars): int
    {
        $failures = 0;

        foreach (array_keys(RegistrarManager::PROVIDERS) as $key) {
            if (! $this->isConfigured($key)) {
                $this->line("{$key}: skipped (credentials not configured).");

                continue;
            }

            try {
                $result = $registrars->for($key)->syncAll();
            } catch (RegistrarException $e) {
                $this->error("{$key}: sync failed — {$e->getMessage()}");
                $failures++;

                continue;
            }

            $this->info("{$key}: synced {$result['synced']} domains ({$result['created']} new).");

            foreach ($result['missing'] as $name) {
                $this->warn("  {$name} is local but no longer at {$key} (transferred out or expired?)");
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
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
