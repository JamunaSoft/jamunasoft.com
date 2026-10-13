<?php

namespace App\Support;

class DomainName
{
    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));

        if (preg_match('~^(?:https?://|//)~i', $value)) {
            $host = parse_url(str_starts_with($value, '//') ? 'https:'.$value : $value, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                return rtrim($host, '.');
            }
        }

        return rtrim($value, '/.');
    }
}
