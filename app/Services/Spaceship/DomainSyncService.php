<?php

namespace App\Services\Spaceship;

use App\Models\Domain;
use Illuminate\Support\Carbon;

class DomainSyncService
{
    public function __construct(protected SpaceshipClient $client) {}

    /**
     * Pull every domain from the Spaceship account into the local database.
     * Local rows that no longer exist at Spaceship are reported, not deleted —
     * a domain may have been transferred out or expired and needs a human look.
     *
     * @return array{synced: int, created: int, missing: array<int, string>}
     */
    public function sync(): array
    {
        $remote = $this->client->allDomains();
        $syncedNames = [];
        $created = 0;

        foreach ($remote as $item) {
            $name = strtolower((string) data_get($item, 'name'));

            if ($name === '') {
                continue;
            }

            $syncedNames[] = $name;
            $existed = Domain::withTrashed()->where('name', $name)->exists();
            $created += $existed ? 0 : 1;

            $this->syncOne($item);
        }

        $missing = Domain::query()
            ->whereNotIn('name', $syncedNames)
            ->pluck('name')
            ->all();

        return [
            'synced' => count($syncedNames),
            'created' => $created,
            'missing' => $missing,
        ];
    }

    /**
     * Fetch a single domain from the API and upsert it locally.
     */
    public function syncByName(string $name): Domain
    {
        return $this->syncOne($this->client->getDomain($name));
    }

    /**
     * Upsert one domain from a Spaceship API payload into the local database.
     *
     * @param  array<string, mixed>  $item
     */
    public function syncOne(array $item): Domain
    {
        $name = strtolower((string) data_get($item, 'name'));

        $domain = Domain::withTrashed()->firstOrNew(['name' => $name]);

        $domain->fill([
            'registrar' => 'spaceship',
            'lifecycle_status' => data_get($item, 'lifecycleStatus'),
            'verification_status' => data_get($item, 'verificationStatus'),
            'auto_renew' => (bool) data_get($item, 'autoRenew', false),
            'is_premium' => (bool) data_get($item, 'isPremium', false),
            'privacy_level' => data_get($item, 'privacyProtection.level'),
            'nameserver_provider' => data_get($item, 'nameservers.provider'),
            'nameservers' => data_get($item, 'nameservers.hosts'),
            'contact_ids' => data_get($item, 'contacts'),
            'epp_statuses' => data_get($item, 'eppStatuses'),
            'registered_at' => $this->parseDate(data_get($item, 'registrationDate')),
            'expires_at' => $this->parseDate(data_get($item, 'expirationDate')),
            'last_synced_at' => now(),
            'meta' => $item,
        ]);

        if ($domain->trashed()) {
            $domain->restore();
        }

        $domain->save();

        return $domain;
    }

    protected function parseDate(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
