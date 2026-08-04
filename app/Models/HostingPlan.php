<?php

namespace App\Models;

use App\Enums\HostingPlanType;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingPlan extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'name', 'type', 'monthly_price', 'yearly_price', 'discounted_price',
        'storage', 'bandwidth', 'websites', 'email_accounts', 'databases',
        'backup_frequency', 'has_ssl', 'support_level', 'features',
        'is_recommended', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => HostingPlanType::class,
            'monthly_price' => 'decimal:2',
            'yearly_price' => 'decimal:2',
            'discounted_price' => 'decimal:2',
            'has_ssl' => 'boolean',
            'features' => 'array',
            'is_recommended' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('monthly_price');
    }
}
