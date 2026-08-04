<?php

namespace App\View\Components;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Service;
use App\Models\SocialLink;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteFooter extends Component
{
    /** @var array<int, array{label: string, url: string}> */
    public array $serviceLinks = [];

    /** @var array<int, array{label: string, url: string}> */
    public array $companyLinks = [];

    /** @var array<int, array{label: string, url: string}> */
    public array $legalLinks = [];

    /** @var array<int, array{platform: string, label: ?string, url: string}> */
    public array $socialLinks = [];

    public function __construct()
    {
        $this->serviceLinks = $this->menuLinks('footer_services') ?: $this->fallbackServiceLinks();
        $this->legalLinks = $this->menuLinks('footer_legal') ?: $this->fallbackLegalLinks();
        $this->companyLinks = $this->menuLinks('footer_company') ?: [
            ['label' => __('About Us'), 'url' => route('about')],
            ['label' => __('Portfolio'), 'url' => route('portfolio.index')],
            ['label' => __('Packages'), 'url' => route('packages.index')],
            ['label' => __('Blog'), 'url' => route('blog.index')],
            ['label' => __('Contact'), 'url' => route('contact.form')],
            ['label' => __('Request a Quotation'), 'url' => route('quote.create')],
        ];

        // Cache plain arrays (never Eloquent models) so unserialization is always safe.
        $this->socialLinks = cache()->remember(
            'site_social_links',
            now()->addHour(),
            fn (): array => SocialLink::query()->active()->ordered()->get()
                ->map(fn (SocialLink $link) => [
                    'platform' => $link->platform,
                    'label' => $link->label,
                    'url' => $link->url,
                ])->all(),
        );
    }

    public function render(): View
    {
        return view('components.site-footer');
    }

    /** @return array<int, array{label: string, url: string}> */
    protected function menuLinks(string $location): array
    {
        return cache()->remember(
            "site_menu:{$location}:".app()->getLocale(),
            now()->addHour(),
            function () use ($location): array {
                $menu = Menu::query()
                    ->where('location', $location)
                    ->with(['items' => fn ($query) => $query->active()])
                    ->first();

                if (! $menu || $menu->items->isEmpty()) {
                    return [];
                }

                return $menu->items->map(fn (MenuItem $item) => [
                    'label' => (string) $item->t('label'),
                    'url' => (string) $item->url,
                ])->all();
            },
        );
    }

    /** @return array<int, array{label: string, url: string}> */
    protected function fallbackServiceLinks(): array
    {
        return cache()->remember(
            'site_footer_services:'.app()->getLocale(),
            now()->addHour(),
            fn (): array => Service::query()->active()->ordered()->take(6)->get()
                ->map(fn (Service $service) => [
                    'label' => (string) $service->t('name'),
                    'url' => route('services.show', $service),
                ])->all(),
        );
    }

    /** @return array<int, array{label: string, url: string}> */
    protected function fallbackLegalLinks(): array
    {
        return cache()->remember(
            'site_legal_pages:'.app()->getLocale(),
            now()->addHour(),
            fn (): array => Page::query()
                ->published()
                ->whereIn('slug', ['privacy-policy', 'terms-of-service', 'terms-and-conditions', 'refund-policy'])
                ->get()
                ->map(fn (Page $page) => [
                    'label' => (string) $page->t('title'),
                    'url' => route('page.show', $page),
                ])->all(),
        );
    }
}
