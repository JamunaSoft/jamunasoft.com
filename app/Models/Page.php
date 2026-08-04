<?php

namespace App\Models;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'template', 'content', 'sections', 'status',
        'seo_title', 'seo_description', 'seo_noindex',
    ];

    protected function casts(): array
    {
        return [
            'template' => PageTemplate::class,
            'sections' => 'array',
            'status' => PublishStatus::class,
            'seo_noindex' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
