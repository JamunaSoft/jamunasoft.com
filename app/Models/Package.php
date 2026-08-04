<?php

namespace App\Models;

use App\Enums\PackageCategory;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'category', 'service_id', 'excerpt',
        'price', 'discounted_price', 'price_suffix', 'is_starting_from',
        'features', 'excluded_features', 'delivery_time', 'support_period',
        'is_recommended', 'is_featured', 'cta_label', 'cta_url',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'category' => PackageCategory::class,
            'price' => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'is_starting_from' => 'boolean',
            'features' => 'array',
            'excluded_features' => 'array',
            'is_recommended' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The price that should be displayed (discounted price wins when set).
     */
    public function displayPrice(): ?string
    {
        $price = $this->discounted_price ?? $this->price;

        return $price === null ? null : number_format((float) $price);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
