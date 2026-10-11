<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhoisRetailPricing
{
    public const URL = 'https://shop.whois.com/domain-registration/domain-registration-price.php';

    public function fetch(): array
    {
        $response = Http::timeout(45)->get(self::URL);
        if (! $response->successful()) {
            throw new RuntimeException('Whois retail pricing could not be fetched (HTTP '.$response->status().'). Last verified USD prices were kept.');
        }

        return $this->parse($response->body());
    }

    /** Only accept explicitly USD, one-year pricing tables with all three actions. */
    public function parse(string $html): array
    {
        if (! preg_match('/\bUSD\b|US\s*\$/i', strip_tags(preg_replace('/<[^>]+>/', ' ', $html)))) {
            throw new RuntimeException('Whois page does not identify its prices as USD. Last verified USD prices were kept.');
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($dom);
        // Ignore crossed-out old prices when a current promotional price exists.
        foreach ($xpath->query('//del | //s | //strike') as $old) {
            $old->parentNode->removeChild($old);
        }
        $prices = [];
        foreach ($xpath->query('//table') as $table) {
            $columns = [];
            foreach ($xpath->query('.//tr', $table) as $row) {
                $cells = [];
                foreach ($xpath->query('./th | ./td', $row) as $cell) {
                    $cells[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent));
                }
                foreach ($cells as $index => $text) {
                    if (preg_match('/register|registration/i', $text)) {
                        $columns['register'] = $index;
                    } elseif (preg_match('/renew/i', $text)) {
                        $columns['renew'] = $index;
                    } elseif (preg_match('/transfer/i', $text)) {
                        $columns['transfer'] = $index;
                    }
                }
                if (count($columns) !== 3 || ! isset($cells[0]) || ! preg_match('/^\.?([a-z0-9-]+(?:\.[a-z0-9-]+)*)$/i', $cells[0], $match)) {
                    continue;
                }
                $entry = [];
                foreach ($columns as $action => $index) {
                    $text = $cells[$index] ?? '';
                    // Reject multi-year-only quotes and ambiguous cells containing multiple amounts.
                    if (preg_match('/\b(?:[2-9]|\d{2,})\s*(?:years?|yrs?)\b/i', $text)
                        || preg_match_all('/(?:US\s*\$|USD\s*|\$)?(\d[\d,]*\.\d{2,4})(?:\s*USD)?/i', $text, $amounts) !== 1) {
                        continue;
                    }
                    $price = (float) str_replace(',', '', $amounts[1][0]);
                    if ($price > 0 && $price < 100000) {
                        $entry[$action] = $price;
                    }
                }
                if (count($entry) === 3) {
                    $prices[strtolower($match[1])] = $entry;
                }
            }
        }
        if ($prices === []) {
            throw new RuntimeException('No complete USD retail pricing table found on Whois. Last verified USD prices were kept.');
        }

        return $prices;
    }
}
