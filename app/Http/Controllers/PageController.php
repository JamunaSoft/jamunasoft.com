<?php

namespace App\Http\Controllers;

use App\Enums\PackageCategory;
use App\Enums\PageTemplate;
use App\Models\Faq;
use App\Models\Package;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        $sections = collect(is_array($page->sections) ? $page->sections : [])
            ->filter(fn ($section) => is_array($section) && filled($section['type'] ?? null))
            ->map(fn (array $section) => [
                'type' => (string) $section['type'],
                'data' => is_array($section['data'] ?? null) ? $section['data'] : [],
            ])
            ->map(fn (array $section) => $this->resolveSection($section))
            ->values();

        $useSections = $page->template === PageTemplate::Landing || $sections->isNotEmpty();

        return view('pages.show', [
            'page' => $page,
            'sections' => $useSections ? $sections : collect(),
            'useSections' => $useSections,
            'seo' => [
                'title' => $page->t('seo_title') ?: $page->t('title'),
                'description' => $page->t('seo_description') ?: str(strip_tags((string) $page->t('content')))->limit(160)->toString(),
                'noindex' => (bool) $page->seo_noindex,
            ],
        ]);
    }

    /**
     * Attach any queried data a section needs, so views stay logic-free.
     *
     * @param  array{type: string, data: array<string, mixed>}  $section
     * @return array{type: string, data: array<string, mixed>, items?: mixed}
     */
    protected function resolveSection(array $section): array
    {
        $data = $section['data'];
        $count = (int) ($data['count'] ?? 0);

        $section['items'] = match ($section['type']) {
            'faq' => Faq::query()->general()->active()->ordered()->take($count > 0 ? $count : 8)->get(),
            'testimonials' => Testimonial::query()->approved()->ordered()->with('media')->take($count > 0 ? $count : 6)->get(),
            'portfolio_grid' => Portfolio::query()->active()->ordered()->with(['media', 'category'])->take($count > 0 ? $count : 6)->get(),
            'service_grid' => Service::query()->active()->ordered()->with('media')->take($count > 0 ? $count : 6)->get(),
            'pricing_grid' => Package::query()->active()->ordered()->with('service')
                ->when(
                    PackageCategory::tryFrom((string) ($data['category'] ?? '')),
                    fn ($query, $category) => $query->where('category', $category),
                )
                ->take($count > 0 ? $count : 3)
                ->get(),
            'contact_form' => Service::query()->active()->ordered()->get(),
            default => null,
        };

        return $section;
    }
}
