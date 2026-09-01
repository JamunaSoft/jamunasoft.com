<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Portfolio extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'portfolio_category_id', 'title', 'slug', 'client_name', 'industry',
        'summary', 'challenge', 'solution', 'key_features', 'technologies', 'results',
        'project_url', 'video_url', 'completed_at', 'testimonial_quote', 'testimonial_author',
        'is_featured', 'is_active', 'sort_order',
        'seo_title', 'seo_description', 'seo_noindex',
    ];

    protected function casts(): array
    {
        return [
            'key_features' => 'array',
            'technologies' => 'array',
            'results' => 'array',
            'completed_at' => 'date',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'seo_noindex' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('client_logo')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->width(800)
            ->format('webp')
            ->nonQueued();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PortfolioCategory::class, 'portfolio_category_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * Embeddable player URL for the project's YouTube/Vimeo video, or null
     * when there is no (recognizable) video link.
     */
    public function videoEmbedUrl(): ?string
    {
        $url = (string) $this->video_url;

        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?.*?v=|shorts/|embed/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,20})~', $url, $matches)) {
            return 'https://www.youtube-nocookie.com/embed/'.$matches[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return null;
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
        return $query->orderBy('sort_order')->orderByDesc('completed_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
