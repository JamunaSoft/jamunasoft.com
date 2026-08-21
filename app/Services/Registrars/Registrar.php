<?php

namespace App\Services\Registrars;

use App\Models\Domain;

/**
 * A domain registrar backend. All methods throw RegistrarException on
 * provider errors so the order pipeline can fail orders uniformly.
 */
interface Registrar
{
    /** Stable identifier stored on domains and orders, e.g. "spaceship". */
    public function key(): string;

    /**
     * @return array{available: bool, premium: bool}
     */
    public function checkAvailability(string $domain): array;

    /**
     * Register a domain for N years using the house registrant contact.
     * Returns an async operation id when the provider is asynchronous,
     * null when the registration settled synchronously.
     *
     * @return array{operationId: ?string}
     */
    public function register(string $domain, int $years): array;

    /**
     * Renew a domain for N years.
     *
     * @return array{operationId: ?string}
     */
    public function renew(string $domain, int $years): array;

    /**
     * Fetch the domain from the provider and upsert the local Domain row.
     */
    public function syncDomain(string $domain): Domain;
}
