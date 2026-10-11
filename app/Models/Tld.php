<?php

namespace App\Models;

use App\Enums\DomainOrderType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tld extends Model
{
    protected $fillable = [
        'tld', 'register_price', 'renew_price', 'transfer_price',
        'is_active', 'sort_order', 'auto_price_sync',
        'register_usd', 'renew_usd', 'transfer_usd', 'prices_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'register_price' => 'decimal:2',
            'renew_price' => 'decimal:2',
            'transfer_price' => 'decimal:2',
            'is_active' => 'boolean',
            'auto_price_sync' => 'boolean',
            'register_usd' => 'decimal:4',
            'renew_usd' => 'decimal:4',
            'transfer_usd' => 'decimal:4',
            'prices_synced_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('tld');
    }

    /**
     * Find the active TLD matching a full domain name, preferring the longest
     * suffix so "example.com.bd" matches "com.bd" over "bd".
     */
    public static function matching(string $domain): ?self
    {
        $labels = explode('.', strtolower(trim($domain, '.')));
        array_shift($labels);

        $suffixes = [];
        while ($labels !== []) {
            $suffixes[] = implode('.', $labels);
            array_shift($labels);
        }

        if ($suffixes === []) {
            return null;
        }

        return static::query()
            ->active()
            ->whereIn('tld', $suffixes)
            ->get()
            ->sortByDesc(fn (self $tld) => strlen($tld->tld))
            ->first();
    }

    public function priceFor(DomainOrderType $type): float
    {
        return (float) match ($type) {
            DomainOrderType::Register => $this->register_price,
            DomainOrderType::Renew => $this->renew_price,
            DomainOrderType::Transfer => $this->transfer_price,
        };
    }
}
