<?php

namespace App\Services\Registrars;

use App\Models\Domain;
use App\Support\Settings;
use Illuminate\Support\Carbon;

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

    public function register(string $domain, int $years): array
    {
        $nameservers = $this->defaultNameservers();

        if (count($nameservers) < 2) {
            throw new RegistrarException('ResellCube registrations need default nameservers — fill "Default nameservers" in the Domains tab of Website Settings (one per line, at least two).');
        }

        $this->client->registerDomain(
            $domain,
            $years,
            $this->customerId(),
            $this->contactId(),
            $nameservers,
        );

        // The LogicBoxes platform settles registrations synchronously.
        return ['operationId' => null];
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

    /**
     * The house customer account that owns every registration (white-label:
     * customers never get their own ResellCube account). Cached in settings.
     */
    protected function customerId(): int
    {
        $cached = (int) settings('resellcube_customer_id', 0);

        if ($cached > 0) {
            return $cached;
        }

        $registrant = $this->registrant();
        [$phoneCc, $phone] = $this->phoneParts($registrant['phone']);

        try {
            $customerId = $this->client->signupCustomer([
                'username' => $registrant['email'],
                'passwd' => str()->password(12).'aA1!',
                'name' => $registrant['name'],
                'company' => $registrant['org'] ?: $registrant['name'],
                'address-line-1' => $registrant['address'],
                'city' => $registrant['city'],
                'state' => $registrant['city'],
                'country' => $registrant['country'],
                'zipcode' => $registrant['postal'] ?: '1000',
                'phone-cc' => $phoneCc,
                'phone' => $phone,
                'lang-pref' => 'en',
            ]);
        } catch (RegistrarException $e) {
            // The email may already have an account — reuse it.
            $existing = $this->client->customerByUsername($registrant['email']);
            $customerId = (int) data_get($existing, 'customerid', 0);

            if ($customerId <= 0) {
                throw $e;
            }
        }

        Settings::set(['resellcube_customer_id' => $customerId], 'domains');

        return $customerId;
    }

    /**
     * The house registrant contact under the house customer. Cached in
     * settings with a hash of the source fields, like the Spaceship one.
     */
    protected function contactId(): int
    {
        $registrant = $this->registrant();
        $hash = md5(json_encode($registrant));

        $cached = (int) settings('resellcube_contact_id', 0);

        if ($cached > 0 && settings('resellcube_contact_hash') === $hash) {
            return $cached;
        }

        [$phoneCc, $phone] = $this->phoneParts($registrant['phone']);

        $contactId = $this->client->addContact([
            'name' => $registrant['name'],
            'company' => $registrant['org'] ?: $registrant['name'],
            'email' => $registrant['email'],
            'address-line-1' => $registrant['address'],
            'city' => $registrant['city'],
            'country' => $registrant['country'],
            'zipcode' => $registrant['postal'] ?: '1000',
            'phone-cc' => $phoneCc,
            'phone' => $phone,
            'type' => 'Contact',
            'customer-id' => $this->customerId(),
        ]);

        Settings::set([
            'resellcube_contact_id' => $contactId,
            'resellcube_contact_hash' => $hash,
        ], 'domains');

        return $contactId;
    }

    /**
     * @return array{name: string, org: string, email: string, phone: string, address: string, city: string, postal: string, country: string}
     */
    protected function registrant(): array
    {
        $registrant = [
            'name' => trim(settings('domain_registrant_first_name', '').' '.settings('domain_registrant_last_name', '')),
            'org' => (string) settings('domain_registrant_org', ''),
            'email' => (string) settings('domain_registrant_email', ''),
            'phone' => (string) settings('domain_registrant_phone', ''),
            'address' => (string) settings('domain_registrant_address', ''),
            'city' => (string) settings('domain_registrant_city', ''),
            'postal' => (string) settings('domain_registrant_postal', ''),
            'country' => (string) settings('domain_registrant_country', 'BD'),
        ];

        foreach (['name', 'email', 'phone', 'address', 'city', 'country'] as $field) {
            if (trim($registrant[$field]) === '') {
                throw new RegistrarException(
                    "Default registrant contact is incomplete: '{$field}' is missing. Fill in the Domains tab of Website Settings."
                );
            }
        }

        return $registrant;
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

    /** @return array<int, string> */
    protected function defaultNameservers(): array
    {
        return collect(explode("\n", (string) settings('domain_default_nameservers', '')))
            ->map(fn (string $host) => strtolower(trim($host)))
            ->filter()
            ->values()
            ->all();
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
