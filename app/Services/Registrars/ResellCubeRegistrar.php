<?php

namespace App\Services\Registrars;

use App\Models\Domain;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ResellCubeRegistrar implements Registrar
{
    public function __construct(protected ResellCubeClient $client) {}

    public function key(): string
    {
        return 'resellcube';
    }

    public function checkAvailability(string $domain): array
    {
        [$sld, $tld] = $this->split($domain);

        $result = $this->client->availability($sld, $tld);
        // Plain array access — data_get() would treat the domain's dot as nesting.
        $entry = $result[strtolower($domain)] ?? [];

        $status = strtolower((string) data_get($entry, 'status', ''));
        $classKey = strtolower((string) data_get($entry, 'classkey', ''));

        return [
            'available' => $status === 'available',
            'premium' => str_contains($classKey, 'premium'),
        ];
    }

    public function register(string $domain, int $years, array $contact): array
    {
        $customerId = $this->customerId($contact);
        $contactId = $this->contactId($contact, $customerId);

        $this->client->registerDomain(
            $domain,
            $years,
            $customerId,
            $contactId,
            default_nameservers(),
        );

        // The LogicBoxes platform settles registrations synchronously.
        return ['operationId' => null];
    }

    public function updateNameservers(string $domain, array $hosts): void
    {
        $this->client->modifyNameservers($this->client->orderIdByDomain($domain), $hosts);
    }

    public function renew(string $domain, int $years): array
    {
        $orderId = $this->client->orderIdByDomain($domain);
        $details = $this->client->orderDetails($orderId);

        $expiry = (int) data_get($details, 'endtime', 0);

        if ($orderId <= 0 || $expiry <= 0) {
            throw new RegistrarException("Could not resolve the ResellCube order for {$domain}.");
        }

        $this->client->renewDomain($orderId, $years, $expiry);

        return ['operationId' => null];
    }

    public function transfer(string $domain, string $authCode, int $years): array
    {
        throw new RegistrarException('ResellCube is the source registrar for this transfer and cannot receive the domain.');
    }

    public function unlockForTransfer(string $domain): void
    {
        $orderId = $this->client->orderIdByDomain($domain);

        if ($orderId <= 0) {
            throw new RegistrarException("Could not resolve the ResellCube order for {$domain}.");
        }

        $this->client->unlockDomain($orderId);
    }

    public function syncDomain(string $domain): Domain
    {
        $details = $this->client->orderDetails($this->client->orderIdByDomain($domain));

        $nameservers = collect(range(1, 13))
            ->map(fn (int $i) => data_get($details, "ns{$i}"))
            ->filter()
            ->values()
            ->all();

        $local = Domain::withTrashed()->firstOrNew(['name' => strtolower($domain)]);

        $local->fill([
            'registrar' => $this->key(),
            'lifecycle_status' => strtolower((string) data_get($details, 'currentstatus', '')) ?: null,
            'auto_renew' => (bool) data_get($details, 'recurring', false),
            'nameserver_provider' => 'custom',
            'nameservers' => $nameservers ?: null,
            'registered_at' => $this->timestamp(data_get($details, 'creationtime')),
            'expires_at' => $this->timestamp(data_get($details, 'endtime')),
            'last_synced_at' => now(),
            'meta' => $details,
        ]);

        if ($local->trashed()) {
            $local->restore();
        }

        $local->save();

        return $local;
    }

    public function syncAll(): array
    {
        $names = $this->client->listDomainNames();
        $created = 0;

        foreach ($names as $name) {
            $existed = Domain::withTrashed()->where('name', $name)->exists();
            $created += $existed ? 0 : 1;

            $this->syncDomain($name);
        }

        $missing = Domain::query()
            ->where('registrar', $this->key())
            ->whereNotIn('name', $names)
            ->pluck('name')
            ->all();

        return [
            'synced' => count($names),
            'created' => $created,
            'missing' => $missing,
        ];
    }

    protected function customerId(array $contact): int
    {
        $key = 'resellcube_customer:'.config('services.resellcube.user_id').':'.hash('sha256', strtolower($contact['email']));
        $cached = (int) Cache::get($key, 0);
        if ($cached > 0) {
            return $cached;
        }

        [$phoneCc, $phone] = $this->phoneParts($contact['phone']);
        $name = $contact['firstName'].' '.$contact['lastName'];
        try {
            $customerId = $this->client->signupCustomer([
                'username' => $contact['email'],
                'passwd' => str()->password(12).'aA1!',
                'name' => $name,
                'company' => $contact['organization'] ?? $name,
                'address-line-1' => $contact['address1'],
                'address-line-2' => $contact['address2'] ?? '',
                'city' => $contact['city'],
                'state' => $contact['stateProvince'] ?? $contact['city'],
                'country' => $contact['country'],
                'zipcode' => $contact['postalCode'],
                'phone-cc' => $phoneCc,
                'phone' => $phone,
                'lang-pref' => 'en',
            ]);
        } catch (RegistrarException $e) {
            $existing = $this->client->customerByUsername($contact['email']);
            $customerId = (int) data_get($existing, 'customerid', 0);
            if ($customerId <= 0) {
                throw $e;
            }
        }

        if ($customerId <= 0) {
            throw new RegistrarException('ResellCube did not return a customer ID for the order customer.');
        }

        Cache::forever($key, $customerId);

        return $customerId;
    }

    protected function contactId(array $contact, int $customerId): int
    {
        $key = 'resellcube_contact:'.config('services.resellcube.user_id').':'.$customerId.':'.hash('sha256', json_encode($contact));
        $cached = (int) Cache::get($key, 0);
        if ($cached > 0) {
            return $cached;
        }

        [$phoneCc, $phone] = $this->phoneParts($contact['phone']);
        $name = $contact['firstName'].' '.$contact['lastName'];
        $contactId = $this->client->addContact([
            'name' => $name,
            'company' => $contact['organization'] ?? $name,
            'email' => $contact['email'],
            'address-line-1' => $contact['address1'],
            'address-line-2' => $contact['address2'] ?? '',
            'city' => $contact['city'],
            'state' => $contact['stateProvince'] ?? '',
            'country' => $contact['country'],
            'zipcode' => $contact['postalCode'],
            'phone-cc' => $phoneCc,
            'phone' => $phone,
            'type' => 'Contact',
            'customer-id' => $customerId,
        ]);

        if ($contactId <= 0) {
            throw new RegistrarException('ResellCube did not return a contact ID for the order customer.');
        }

        Cache::forever($key, $contactId);

        return $contactId;
    }

    /**
     * Split an EPP-format phone (+880.1700000000) into [country code, number].
     *
     * @return array{0: string, 1: string}
     */
    protected function phoneParts(string $phone): array
    {
        if (preg_match('/^\+(\d{1,3})\.(\d+)$/', trim($phone), $matches)) {
            return [$matches[1], $matches[2]];
        }

        throw new RegistrarException('Registrant phone must be in +<countrycode>.<number> format, e.g. +880.1700000000.');
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function split(string $domain): array
    {
        [$sld, $tld] = explode('.', strtolower($domain), 2);

        return [$sld, $tld];
    }

    protected function timestamp(mixed $value): ?Carbon
    {
        $value = (int) $value;

        return $value > 0 ? Carbon::createFromTimestamp($value) : null;
    }
}
