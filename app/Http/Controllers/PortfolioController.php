<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Models\PortfolioCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $request): View
    {
        $categories = PortfolioCategory::query()->active()->ordered()->get();

        $activeCategory = null;

        if ($slug = $request->query('category')) {
            $activeCategory = $categories->firstWhere('slug', $slug);
        }

        $portfolios = Portfolio::query()
            ->active()
            ->ordered()
            ->when($activeCategory, fn ($query) => $query->where('portfolio_category_id', $activeCategory->id))
            ->with(['media', 'category'])
            ->paginate(9)
            ->withQueryString();

        return view('portfolio.index', [
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'portfolios' => $portfolios,
            'seo' => [
                'title' => __('Our Portfolio'),
                'description' => __('Explore case studies of websites, software and digital projects delivered by Jamuna Soft.'),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $portfolio = Portfolio::query()
            ->active()
            ->where('slug', $slug)
            ->with(['media', 'category', 'services' => fn ($query) => $query->active()->ordered()])
            ->firstOrFail();

        $related = Portfolio::query()
            ->active()
            ->ordered()
            ->whereKeyNot($portfolio->id)
            ->when(
                $portfolio->portfolio_category_id,
                fn ($query) => $query->where('portfolio_category_id', $portfolio->portfolio_category_id),
            )
            ->with(['media', 'category'])
            ->take(3)
            ->get();

        return view('portfolio.show', [
            'portfolio' => $portfolio,
            'related' => $related,
            'seo' => [
                'title' => $portfolio->t('seo_title') ?: $portfolio->t('title'),
                'description' => $portfolio->t('seo_description') ?: str(strip_tags((string) $portfolio->t('summary')))->limit(160),
                'image' => $portfolio->getFirstMediaUrl('featured', 'card') ?: null,
                'noindex' => (bool) $portfolio->seo_noindex,
            ],
        ]);
    }
}
