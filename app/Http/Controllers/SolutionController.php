<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\Solution;
use Illuminate\Contracts\View\View;

class SolutionController extends Controller
{
    public function index(): View
    {
        return view('solutions.index', [
            'solutions' => Solution::query()->active()->ordered()->with('media')->get(),
            'seo' => [
                'title' => __('Industry Solutions'),
                'description' => __('Tailored software and digital solutions for education, healthcare, retail, logistics and more industries in Bangladesh.'),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $solution = Solution::query()
            ->active()
            ->where('slug', $slug)
            ->with([
                'media',
                'services' => fn ($query) => $query->active()->ordered()->with('media'),
            ])
            ->firstOrFail();

        $relatedPortfolios = Portfolio::query()
            ->active()
            ->ordered()
            ->whereHas('services', fn ($query) => $query->whereIn(
                'services.id',
                $solution->services->pluck('id'),
            ))
            ->with(['media', 'category'])
            ->take(3)
            ->get();

        return view('solutions.show', [
            'solution' => $solution,
            'relatedPortfolios' => $relatedPortfolios,
            'seo' => [
                'title' => $solution->t('seo_title') ?: $solution->t('name'),
                'description' => $solution->t('seo_description') ?: str(strip_tags((string) $solution->t('excerpt')))->limit(160),
                'image' => $solution->getFirstMediaUrl('featured', 'card') ?: null,
                'noindex' => (bool) $solution->seo_noindex,
            ],
        ]);
    }
}
