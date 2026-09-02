<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal WHMCS API client, used to migrate client/domain relations
 * from the legacy billing.jamunasoft.com installation.
 */
class WhmcsClient
{
    public function __construct(
        protected ?string $url = null,
        protected ?string $identifier = null,
        protected ?string $secret = null,
    ) {
        $this->url ??= (string) config('services.whmcs.url');
        $this->identifier ??= (string) config('services.whmcs.identifier');
        $this->secret ??= (string) config('services.whmcs.secret');
    }

    /**
     * All client domains: domainname, userid, status, expirydate, ...
     *
     * @return array<int, array<string, mixed>>
     */
    public function allDomains(): array
    {
        return $this->paginate('GetClientsDomains', 'domains.domain');
    }

    /**
     * All clients: id, name, email, ...
     *
     * @return array<int, array<string, mixed>>
     */
    public function allClients(): array
    {
        return $this->paginate('GetClients', 'clients.client');
    }

    /**
     * Full profile of one client: companyname, phonenumber, address1,
     * city, postcode, country, ...
     *
     * @return array<string, mixed>
     */
    public function clientDetails(int $clientId): array
    {
        return $this->call('GetClientsDetails', ['clientid' => $clientId]);
    }

    /**
     * All client products/services: name, domain, billingcycle,
     * recurringamount, nextduedate, status, ...
     *
     * @return array<int, array<string, mixed>>
     */
    public function allProducts(): array
    {
        return $this->paginate('GetClientsProducts', 'products.product');
    }

    /**
     * All invoices with the given status (Unpaid, Paid, Cancelled, ...).
     *
     * @return array<int, array<string, mixed>>
     */
    public function invoicesByStatus(string $status): array
    {
        return $this->paginate('GetInvoices', 'invoices.invoice', ['status' => $status]);
    }

    /**
     * One invoice with its line items and balance.
     *
     * @return array<string, mixed>
     */
    public function invoice(int $invoiceId): array
    {
        return $this->call('GetInvoice', ['invoiceid' => $invoiceId]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<int, array<string, mixed>>
     */
    protected function paginate(string $action, string $itemsPath, array $extra = []): array
    {
        $items = [];
        $start = 0;

        do {
            $page = $this->call($action, [...$extra, 'limitstart' => $start, 'limitnum' => 100]);
            $pageItems = (array) data_get($page, $itemsPath, []);

            $items = [...$items, ...$pageItems];
            $start += 100;
            $total = (int) data_get($page, 'totalresults', 0);
        } while ($pageItems !== [] && count($items) < $total);

        return $items;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function call(string $action, array $params = []): array
    {
        if ($this->url === '' || $this->identifier === '') {
            throw new RuntimeException('WHMCS API credentials are not configured. Set WHMCS_API_URL, WHMCS_API_IDENTIFIER and WHMCS_API_SECRET in .env.');
        }

        $response = Http::asForm()->timeout(30)->post($this->url, [
            'action' => $action,
            'identifier' => $this->identifier,
            'secret' => $this->secret,
            'responsetype' => 'json',
            ...$params,
        ]);

        $body = $response->json();

        if ($response->failed() || data_get($body, 'result') !== 'success') {
            throw new RuntimeException('WHMCS API '.$action.' failed: '.(data_get($body, 'message') ?? 'HTTP '.$response->status()));
        }

        return (array) $body;
    }
}
