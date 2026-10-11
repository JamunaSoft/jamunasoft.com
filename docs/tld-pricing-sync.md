# TLD catalog and retail pricing

`domains:import-tld-catalog` imports the bundled IANA root-zone list (10 October 2026, 1,437 TLDs). It runs during `deploy.sh` after migrations. It adds inactive entries with price sync enabled, preserves existing entries and prices, and is safe to run again. Root-zone membership does not imply that a TLD can be registered through the configured registrar. Enable entries only after confirming support and valid pricing. Whois pricing can also add multi-label suffixes such as `co.uk` when they appear in its table.

`domains:sync-tld-prices` fetches USD/BDT from ExchangeRate-API, recalculates BDT prices from previously stored USD prices, and attempts to import public retail pricing from https://shop.whois.com/domain-registration/domain-registration-price.php. It does not use reseller credentials or reseller cost pricing. ExchangeRate-API attribution: https://www.exchangerate-api.com . Its free open endpoint updates daily.

The scheduler runs `domains:sync-tld-prices --scheduled` at 05:00 in the application timezone. The production cron must run `schedule:run`. TLD Pricing → Pricing settings controls automatic sync, profit percentage, fixed BDT profit and the currency/card fee buffer. Defaults are automatic sync on, margins and buffer zero. Prices round up to the next whole taka:

`USD × USD/BDT × (1 + buffer/100) × (1 + profit/100) + fixed BDT profit`

Existing manually maintained TLD entries opt out by default. Toggle “Sync Whois retail prices” on to opt in. Imported entries stay inactive even after successful price sync. Customer-specific domain renewal prices and existing invoices are unaffected.

The public Whois page returned HTTP 403 from this environment on 11 October 2026. Initial live retail import has therefore not been verified or completed. The table parser requires explicitly USD pricing and distinct registration, renewal and transfer columns; it rejects ambiguous or multi-year-only quotes. If the site blocks the request or changes its format, the UI displays the error and retains the last verified USD prices. Daily currency conversion can still update BDT prices for entries with previously stored USD prices. Entries with no USD prices stay unchanged. You can enter verified USD values in the TLD edit form as a fallback; no approximate or invented prices are imported.

The rate is indicative, not a guaranteed card settlement rate. Use the configurable buffer to cover payment costs. Rates older than three days are rejected. “Price updated” records the last successful source import, not a currency-only recalculation.
