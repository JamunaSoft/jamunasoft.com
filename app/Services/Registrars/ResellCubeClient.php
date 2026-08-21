<?php

namespace App\Services\Registrars;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the ResellCube (LogicBoxes-platform) HTTP API.
 * All parameters travel as query string; auth is auth-userid + api-key.
 */
class ResellCubeClient
{
    public function __construct(
        protected ?string $userId = null,
        protected ?string $apiKey = null,
        protected ?string $baseUrl = null,
    ) {
        $this->userId ??= (string) config('services.resellcube.user_id');
        $this->apiKey ??= (string) config('services.resellcube.api_key');
        $this->baseUrl ??= rtrim((string) config('services.resellcube.base_url'), '/');
    }

    /**
     * @return array<string, mixed> map of "domain.tld" => {status, classkey}
     */
    public function availability(string $sld, string $tld): array
    {
        return $this->call('GET', '/domains/available.json', [
            'domain-name' => $sld,
            'tlds' => $tld,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function signupCustomer(array $attributes): int
    {
        $result = $this->call('POST', '/customers/v2/signup.json', $attributes);

        return (int) (is_array($result) ? data_get($result, 'customerid', 0) : $result);
    }

    /** @return array<string, mixed> */
    public function customerByUsername(string $username): array
    {
        return $this->call('GET', '/customers/details.json', ['username' => $username]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function addContact(array $attributes): int
    {
        $result = $this->call('POST', '/contacts/add.json', $attributes);

        return (int) (is_array($result) ? data_get($result, 'contactid', 0) : $result);
    }

    /**
     * @param  array<int, string>  $nameservers
     * @return array<string, mixed>
     */
    public function registerDomain(string $domain, int $years, int $customerId, int $contactId, array $nameservers): array
    {
        return $this->call('POST', '/domains/register.json', [
            'domain-name' => $domain,
            'years' => $years,
            'ns' => $nameservers,
            'customer-id' => $customerId,
            'reg-contact-id' => $contactId,
            'admin-contact-id' => $contactId,
            'tech-contact-id' => $contactId,
            'billing-contact-id' => $contactId,
            'invoice-option' => 'NoInvoice',
        ]);
    }

    /**
     * Domain names of all domain orders in the reseller account, paginated
     * through /domains/search.json.
     *
     * @return array<int, string>
     */
    public function listDomainNames(): array
    {
        $names = [];
        $page = 1;

        do {
            $result = $this->call('GET', '/domains/search.json', [
                'no-of-records' => 100,
                'page-no' => $page,
            ]);

            $total = (int) data_get($result, 'recsindb', 0);
            $pageCount = 0;

            foreach ((array) $result as $key => $record) {
                if (! is_numeric($key) || ! is_array($record)) {
                    continue;
                }

                $pageCount++;
                $name = strtolower((string) ($record['entity.description'] ?? $record['domainname'] ?? ''));

                if ($name !== '') {
                    $names[] = $name;
                }
            }

            $page++;
        } while ($pageCount > 0 && count($names) < $total && $page < 50);

        return array_values(array_unique($names));
    }

    public function orderIdByDomain(string $domain): int
    {
        return (int) $this->call('GET', '/domains/orderid.json', ['domain-name' => $domain]);
    }

    /** @return array<string, mixed> */
    public function orderDetails(int $orderId): array
    {
        return $this->call('GET', '/domains/details.json', [
            'order-id' => $orderId,
            'options' => 'All',
        ]);
    }

    /** @return array<string, mixed> */
    public function renewDomain(int $orderId, int $years, int $currentExpiryTimestamp): array
    {
        return $this->call('POST', '/domains/renew.json', [
            'order-id' => $orderId,
            'years' => $years,
            'exp-date' => $currentExpiryTimestamp,
            'invoice-option' => 'NoInvoice',
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    protected function call(string $method, string $path, array $params = []): mixed
    {
        if ($this->userId === '' || $this->apiKey === '') {
            throw new RegistrarException('ResellCube API credentials are not configured. Set RESELLCUBE_USER_ID and RESELLCUBE_API_KEY in .env.');
        }

        $query = [
            'auth-userid' => $this->userId,
            'api-key' => $this->apiKey,
            ...$params,
        ];

        $response = Http::acceptJson()
            ->timeout(30)
            ->retry(3, fn (int $attempt) => $attempt * 1000, function (\Exception $e) {
                if ($e instanceof ConnectionException) {
                    return true;
                }

                return $e instanceof RequestException
                    && ($e->response->status() === 429 || $e->response->serverError());
            }, throw: false)
            ->withOptions(['query' => $this->buildQuery($query)])
            ->send($method, $this->baseUrl.$path);

        return $this->parse($response);
    }

    /**
     * LogicBoxes repeats array params (ns=a&ns=b) instead of PHP-style ns[],
     * so the query string is built by hand.
     *
     * @param  array<string, mixed>  $query
     */
    protected function buildQuery(array $query): string
    {
        $parts = [];

        foreach ($query as $key => $value) {
            foreach ((array) $value as $item) {
                $parts[] = rawurlencode($key).'='.rawurlencode((string) $item);
            }
        }

        return implode('&', $parts);
    }

    protected function parse(Response $response): mixed
    {
        $body = $response->json();

        // The API signals errors both via HTTP status and a status field
        // ({"status": "ERROR", "message": "..."}), sometimes with HTTP 200.
        $status = is_array($body) ? strtoupper((string) data_get($body, 'status', '')) : '';

        if ($response->failed() || in_array($status, ['ERROR', 'FAILED'], true)) {
            $message = is_array($body)
                ? (data_get($body, 'message') ?? data_get($body, 'error'))
                : null;

            throw new RegistrarException(
                is_string($message) && $message !== ''
                    ? $message
                    : 'ResellCube API request failed with HTTP '.$response->status().'.'
            );
        }

        return $body;
    }
}
