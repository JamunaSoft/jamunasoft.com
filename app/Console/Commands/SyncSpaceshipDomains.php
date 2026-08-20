<?php

namespace App\Console\Commands;

use App\Services\Spaceship\DomainSyncService;
use App\Services\Spaceship\SpaceshipException;
use Illuminate\Console\Command;

class SyncSpaceshipDomains extends Command
{
    protected $signature = 'spaceship:sync';

    protected $description = 'Sync all domains from the Spaceship account into the local database';

    public function handle(DomainSyncService $sync): int
    {
        try {
            $result = $sync->sync();
        } catch (SpaceshipException $e) {
            $this->error('Spaceship sync failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Synced {$result['synced']} domains ({$result['created']} new).");

        if ($result['missing'] !== []) {
            $this->warn('Local domains no longer present at Spaceship (transferred out or expired?):');

            foreach ($result['missing'] as $name) {
                $this->line("  - {$name}");
            }
        }

        return self::SUCCESS;
    }
}
