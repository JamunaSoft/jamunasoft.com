<?php

namespace App\Services\Spaceship;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Spaceship public API (https://docs.spaceship.dev).
 *
 * All methods throw SpaceshipException on API errors. Long-running operations
 * (registration, transfer) return an async operation id that must be polled
 * via getAsyncOperation() until it reports success or failure.
 */
class SpaceshipClient
{
    public function __construct(
        protected ?string $key = null,
        protected ?string $secret = null,
        protected ?string $baseUrl = null,
    ) {
        $this->key ??= (string) config('services.spaceship.key');
        $this->secret ??= (string) config('services.spaceship.secret');
        $this->baseUrl ??= rtrim((string) config('services.spaceship.base_url'), '/');
    }

    // ------------------------------------------------------------------
    // Domains
    // ------------------------------------------------------------------

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function listDomains(int $take = 100, int $skip = 0): array
    {
        $body = $this->send('GET', '/domains', query: ['take' => $take, 'skip' => $skip])->json();

        return [
            'items' => data_get($body, 'items', []),
            'total' => (int) data_get($body, 'total', 0),
        ];
    }

    /**
     * Every domain in the account, transparently paginated.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allDomains(): array
    {
        $items = [];
        $skip = 0;
        $take = 100;

        do {
            $page = $this->listDomains($take, $skip);
            $items = [...$items, ...$page['items']];
            $skip += $take;
        } while (count($items) < $page['total'] && $page['items'] !== []);

        return $items;
    }

    /** @return array<string, mixed> */
    public function getDomain(string $domain): array
    {
        return $this->send('GET', "/domains/{$domain}")->json();
    }

    /** @return array<string, mixed> */
    public function checkAvailability(string $domain): array
    {
        return $this->send('GET', "/domains/{$domain}/available")->json();
    }

    /**
     * The API reports availability as result: "available" | "taken".
     *
     * @param  array<string, mixed>  $availability  response from checkAvailability()
     */
    public static function isAvailable(array $availability): bool
    {
        $result = strtolower((string) data_get($availability, 'result', ''));

        return $result === 'available' || (bool) data_get($availability, 'isAvailable', false);
    }

    /**
     * @param  array<string, mixed>  $availability  response from checkAvailability()
     */
    public static function isPremium(array $availability): bool
    {
        return strtolower((string) data_get($availability, 'result', '')) === 'premium'
            || filled(data_get($availability, 'premiumPricing'))
            || (bool) data_get($availability, 'isPremium', false);
    }

    /**
     * Register a domain. Contact ids must be created via saveContact() first.
     *
     * @param  array{registrant: string, admin: string, tech: string, billing: string}  $contacts
     * @return array{operationId: ?string, data: array<string, mixed>}
     */
    public function registerDomain(
        string $domain,
        array $contacts,
        int $years = 1,
        bool $autoRenew = false,
        string $privacyLevel = 'high',
    ): array {
        $response = $this->send('POST', "/domains/{$domain}", [
            'autoRenew' => $autoRenew,
            'years' => $years,
            'privacyProtection' => [
                'level' => $privacyLevel,
                'userConsent' => true,
            ],
            'contacts' => $contacts,
        ]);

        return $this->asyncResult($response);
    }

    /**
     * Renew a domain. The current expiration date is required by the API as a
     * safeguard against double renewals.
     *
     * @return array{operationId: ?string, data: array<string, mixed>}
     */
    public function renewDomain(string $domain, int $years, string $currentExpirationDate): array
    {
        $response = $this->send('POST', "/domains/{$domain}/renew", [
            'years' => $years,
            'currentExpirationDate' => $currentExpirationDate,
        ]);

        return $this->asyncResult($response);
    }

    /**
     * @param  array<int, string>  $hosts
     */
    public function updateNameservers(string $domain, array $hosts, string $provider = 'custom'): void
    {
        $this->send('PUT', "/domains/{$domain}/nameservers", [
            'provider' => $provider,
            'hosts' => $hosts,
        ]);
    }

    // ------------------------------------------------------------------
    // Contacts
    // ------------------------------------------------------------------

    /**
     * Create (or deduplicate) a contact and return its Spaceship contact id.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function saveContact(array $attributes): string
    {
        $body = $this->send('PUT', '/contacts', $attributes)->json();

        return (string) data_get($body, 'contactId');
    }

    /** @return array<string, mixed> */
    public function getContact(string $contactId): array
    {
        return $this->send('GET', "/contacts/{$contactId}")->json();
    }

    // ------------------------------------------------------------------
    // DNS records
    // ------------------------------------------------------------------

    /**
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function listDnsRecords(string $domain, int $take = 100, int $skip = 0): array
    {
        $body = $this->send('GET', "/dns/records/{$domain}", query: ['take' => $take, 'skip' => $skip])->json();

        return [
            'items' => data_get($body, 'items', []),
            'total' => (int) data_get($body, 'total', 0),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function saveDnsRecords(string $domain, array $items, bool $force = false): void
    {
        $this->send('PUT', "/dns/records/{$domain}", ['force' => $force, 'items' => $items]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function deleteDnsRecords(string $domain, array $items): void
    {
        $this->send('DELETE', "/dns/records/{$domain}", $items);
    }

    // ------------------------------------------------------------------
    // Async operations
    // ------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function getAsyncOperation(string $operationId): array
    {
        return $this->send('GET', "/async-operations/{$operationId}")->json();
    }

    // ------------------------------------------------------------------
    // Plumbing
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     */
    protected function send(string $method, string $path, ?array $body = null, array $query = []): Response
    {
        if ($this->key === '' || $this->secret === '') {
            throw SpaceshipException::missingCredentials();
        }

        $response = $this->request()->send($method, $this->baseUrl.$path, array_filter([
            'query' => $query,
            'json' => $body,
        ]));

        if ($response->failed()) {
            throw SpaceshipException::fromResponse($response);
        }

        return $response;
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-API-Key' => $this->key,
            'X-API-Secret' => $this->secret,
        ])
            ->acceptJson()
            ->timeout(30)
            // Back off and retry when rate-limited or on transient failures.
            ->retry(3, fn (int $attempt) => $attempt * 1000, function (\Exception $e) {
                if ($e instanceof ConnectionException) {
                    return true;
                }

                return $e instanceof RequestException
                    && ($e->response->status() === 429 || $e->response->serverError());
            }, throw: false);
    }

    /**
     * @return array{operationId: ?string, data: array<string, mixed>}
     */
    protected function asyncResult(Response $response): array
    {
        return [
            'operationId' => $response->header('spaceship-async-operationid') ?: null,
            'data' => $response->json() ?? [],
        ];
    }
}
