<?php

namespace App\Services\Registrars;

use App\Models\Domain;
use App\Services\Spaceship\DefaultContactService;
use App\Services\Spaceship\DomainSyncService;
use App\Services\Spaceship\SpaceshipClient;

class SpaceshipRegistrar implements Registrar
{
    public function __construct(
        protected SpaceshipClient $client,
        protected DefaultContactService $contacts,
        protected DomainSyncService $sync,
    ) {}

    public function key(): string
    {
        return 'spaceship';
    }

    public function checkAvailability(string $domain): array
    {
        $availability = $this->client->checkAvailability($domain);

        return [
            'available' => SpaceshipClient::isAvailable($availability),
            'premium' => SpaceshipClient::isPremium($availability),
        ];
    }

    public function register(string $domain, int $years): array
    {
        $contactId = $this->contacts->contactId();

        $result = $this->client->registerDomain($domain, [
            'registrant' => $contactId,
            'admin' => $contactId,
            'tech' => $contactId,
            'billing' => $contactId,
        ], years: $years, autoRenew: false, privacyLevel: 'high');

        return ['operationId' => $result['operationId']];
    }

    public function renew(string $domain, int $years): array
    {
        $local = Domain::query()->where('name', $domain)->first();

        if ($local?->expires_at === null) {
            throw new RegistrarException("{$domain} is not in the local domain list — sync from Spaceship before renewing.");
        }

        $result = $this->client->renewDomain(
            $domain,
            $years,
            $local->expires_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
        );

        return ['operationId' => $result['operationId']];
    }

    public function syncDomain(string $domain): Domain
    {
        return $this->sync->syncByName($domain);
    }
}
