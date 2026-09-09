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

    public function transfer(string $domain, string $authCode, int $years): array
    {
        $contactId = $this->transferContactId($domain);
        $result = $this->client->transferDomain($domain, $authCode, [
            'registrant' => $contactId,
            'admin' => $contactId,
            'tech' => $contactId,
            'billing' => $contactId,
        ], years: $years);

        return ['operationId' => $result['operationId']];
    }

    protected function transferContactId(string $domain): string
    {
        $local = Domain::query()->where('name', $domain)->first();
        $source = data_get($local?->meta, 'registrantcontact', []);
        $name = preg_split('/\s+/', trim((string) data_get($source, 'name', '')), 2);
        $countryCode = preg_replace('/\D/', '', (string) data_get($source, 'telnocc', ''));
        $number = preg_replace('/\D/', '', (string) data_get($source, 'telno', ''));
        $attributes = [
            'firstName' => $name[0] ?? '',
            'lastName' => $name[1] ?? '',
            'organization' => data_get($source, 'company', ''),
            'email' => data_get($source, 'emailaddr', ''),
            'address1' => data_get($source, 'address1', ''),
            'address2' => data_get($source, 'address2', ''),
            'city' => data_get($source, 'city', ''),
            'stateProvince' => data_get($source, 'state', ''),
            'postalCode' => data_get($source, 'zip', ''),
            'country' => strtoupper((string) data_get($source, 'country', '')),
            'phone' => $countryCode !== '' && $number !== '' ? "+{$countryCode}.{$number}" : '',
        ];
        $attributes = array_map(fn ($value) => trim((string) $value), $attributes);

        foreach (['firstName', 'lastName', 'email', 'address1', 'city', 'country', 'phone'] as $field) {
            if ($attributes[$field] === '') {
                throw new RegistrarException("Domain owner contact for {$domain} is incomplete: '{$field}' is missing. Update the registrant contact at ResellCube and sync the domain before retrying.");
            }
        }

        $contactId = $this->client->saveContact(array_filter($attributes, fn ($value) => $value !== ''));

        if ($contactId === '') {
            throw new RegistrarException("Spaceship did not return a contact ID for {$domain}.");
        }

        return $contactId;
    }

    public function unlockForTransfer(string $domain): void
    {
        // Spaceship is the receiving registrar in the current transfer flow.
    }

    public function updateNameservers(string $domain, array $hosts): void
    {
        $this->client->updateNameservers($domain, $hosts);
    }

    public function syncDomain(string $domain): Domain
    {
        return $this->sync->syncByName($domain);
    }

    public function syncAll(): array
    {
        return $this->sync->sync();
    }
}
