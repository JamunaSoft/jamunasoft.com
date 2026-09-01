<?php

namespace App\Filament\Client\Widgets;

use App\Models\HostingPlan;
use App\Models\Package;
use Filament\Widgets\Widget;

class PromoProducts extends Widget
{
    protected string $view = 'filament.client.widgets.promo-products';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        return HostingPlan::where('is_active', true)->exists()
            || Package::where('is_active', true)->exists();
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $plans = HostingPlan::query()
            ->where('is_active', true)
            ->orderByDesc('is_recommended')
            ->orderBy('sort_order')
            ->take(3)
            ->get()
            ->map(fn (HostingPlan $plan) => [
                'name' => $plan->name,
                'price' => (float) ($plan->discounted_price ?: ($plan->yearly_price ?: $plan->monthly_price)),
                'suffix' => $plan->yearly_price ? '/year' : '/month',
            ]);

        $packages = Package::query()
            ->where('is_active', true)
            ->orderByDesc('is_recommended')
            ->orderBy('sort_order')
            ->take(max(0, 3 - $plans->count()))
            ->get()
            ->map(fn (Package $package) => [
                'name' => $package->name,
                'price' => (float) ($package->discounted_price ?: $package->price),
                'suffix' => (string) $package->price_suffix,
            ]);

        return ['products' => $plans->concat($packages)];
    }
}
