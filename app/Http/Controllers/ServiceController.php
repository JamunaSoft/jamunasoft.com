<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $categories = ServiceCategory::query()
            ->active()
            ->ordered()
            ->with(['services' => fn ($query) => $query->active()->ordered()->with('media')])
            ->get()
            ->filter(fn (ServiceCategory $category) => $category->services->isNotEmpty())
            ->values();

        $uncategorized = Service::query()
            ->active()
            ->whereNull('service_category_id')
            ->ordered()
            ->with('media')
            ->get();

        return view('services.index', [
            'categories' => $categories,
            'uncategorized' => $uncategorized,
            'seo' => [
                'title' => __('Our Services'),
                'description' => __('Software development, web design, e-commerce, hosting and digital marketing services from Jamuna Soft.'),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $service = Service::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'media',
                'category',
                'faqs' => fn ($query) => $query->active()->ordered(),
                'portfolios' => fn ($query) => $query->active()->ordered()->with(['media', 'category']),
                'packages' => fn ($query) => $query->active()->ordered(),
            ])
            ->firstOrFail();

        return view('services.show', [
            'service' => $service,
            'relatedPortfolios' => $service->portfolios->take(3),
            'relatedPackages' => $service->packages->take(3),
            'seo' => [
                'title' => $service->t('seo_title') ?: $service->t('name'),
                'description' => $service->t('seo_description') ?: str(strip_tags((string) $service->t('excerpt')))->limit(160),
                'image' => $service->getFirstMediaUrl('featured', 'card') ?: null,
                'noindex' => (bool) $service->seo_noindex,
            ],
        ]);
    }
}
