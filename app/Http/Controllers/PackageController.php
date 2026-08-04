<?php

namespace App\Http\Controllers;

use App\Enums\PackageCategory;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $activeCategory = PackageCategory::tryFrom((string) $request->query('category'));

        $packages = Package::query()
            ->active()
            ->ordered()
            ->when($activeCategory, fn ($query) => $query->where('category', $activeCategory))
            ->with('service')
            ->get();

        return view('packages.index', [
            'categories' => PackageCategory::cases(),
            'activeCategory' => $activeCategory,
            'packages' => $packages,
            'seo' => [
                'title' => __('Pricing & Packages'),
                'description' => __('Transparent pricing packages for websites, e-commerce, software, hosting and digital marketing.'),
            ],
        ]);
    }
}
