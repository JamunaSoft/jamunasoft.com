<?php

namespace App\Services;

use App\Models\Tld;
use App\Services\Registrars\RegistrarException;
use App\Services\Registrars\RegistrarManager;
use Illuminate\Support\Facades\Cache;

class DomainSearchService
{
    /** How many TLD suggestions to check for a keyword-only search. */
    protected const MAX_SUGGESTIONS = 6;

    public function __construct(protected RegistrarManager $registrars) {}

    /**
     * Check availability for a search query: an exact domain when the query
     * contains a dot, otherwise the keyword across the active TLDs.
     * Results are cached briefly to be gentle on the registrar API.
     *
     * @return array{results: array<int, array{domain: string, tld: ?string, available: ?bool, premium: bool, price: ?float}>, error: ?string}
     */
    public function search(string $query): array
    {
        $query = $this->sanitize($query);

        if ($query === '') {
            return ['results' => [], 'error' => __('Please enter a domain name to search.')];
        }

        $candidates = str_contains($query, '.')
            ? [$query]
            : Tld::query()->active()->ordered()->limit(self::MAX_SUGGESTIONS)->pluck('tld')
                ->map(fn (string $tld) => "{$query}.{$tld}")
                ->all();

        if ($candidates === []) {
            return ['results' => [], 'error' => __('Domain pricing is being set up — please contact us to order a domain.')];
        }

        $results = [];

        foreach ($candidates as $candidate) {
            if (! $this->isValidDomain($candidate)) {
                return ['results' => [], 'error' => __('That does not look like a valid domain name.')];
            }

            $tld = Tld::matching($candidate);

            if ($tld === null) {
                $results[] = ['domain' => $candidate, 'tld' => null, 'available' => null, 'premium' => false, 'price' => null];

                continue;
            }

            $registrar = $this->registrars->active();

            try {
                $availability = Cache::remember(
                    "domain-availability:{$registrar->key()}:{$candidate}",
                    now()->addMinutes(2),
                    fn () => $registrar->checkAvailability($candidate),
                );
            } catch (RegistrarException $e) {
                return [
                    'results' => $results,
                    'error' => __('The availability check is temporarily unavailable — please try again in a moment.'),
                ];
            }

            $results[] = [
                'domain' => $candidate,
                'tld' => $tld->tld,
                'available' => $availability['available'],
                'premium' => $availability['premium'],
                'price' => $availability['premium'] ? null : (float) $tld->register_price,
            ];
        }

        return ['results' => $results, 'error' => null];
    }

    public function sanitize(string $query): string
    {
        $query = strtolower(trim($query));
        $query = (string) preg_replace('#^https?://#', '', $query);
        $query = (string) preg_replace('#^www\.#', '', $query);
        $query = explode('/', $query)[0];

        return trim(str_replace(' ', '', $query), " \t.");
    }

    public function isValidDomain(string $domain): bool
    {
        if (strlen($domain) > 253 || ! str_contains($domain, '.')) {
            return false;
        }

        foreach (explode('.', $domain) as $label) {
            if (! preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?$/', $label)) {
                return false;
            }
        }

        return true;
    }
}
