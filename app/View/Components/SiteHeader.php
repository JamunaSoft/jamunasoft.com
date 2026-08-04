<?php

namespace App\View\Components;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SiteHeader extends Component
{
    /** @var array<int, array{label: string, url: string, target: ?string, children: array<int, array{label: string, url: string, target: ?string}>}> */
    public array $items = [];

    public function __construct()
    {
        // Cache plain arrays (never Eloquent models) so unserialization is always safe.
        $items = cache()->remember(
            'site_menu:header:'.app()->getLocale(),
            now()->addHour(),
            function (): array {
                $menu = Menu::query()
                    ->where('location', 'header')
                    ->with(['items' => fn ($query) => $query->active()->with(['children' => fn ($children) => $children->active()])])
                    ->first();

                if (! $menu || $menu->items->isEmpty()) {
                    return [];
                }

                return $menu->items->map(fn (MenuItem $item) => [
                    'label' => (string) $item->t('label'),
                    'url' => (string) $item->url,
                    'target' => $item->target,
                    'children' => $item->children->map(fn (MenuItem $child) => [
                        'label' => (string) $child->t('label'),
                        'url' => (string) $child->url,
                        'target' => $child->target,
                    ])->all(),
                ])->all();
            },
        );

        $this->items = $items ?: $this->fallbackItems();
    }

    public function render(): View
    {
        return view('components.site-header');
    }

    /** @return array<int, array{label: string, url: string, target: ?string, children: array<int, mixed>}> */
    protected function fallbackItems(): array
    {
        $items = [
            [__('Home'), route('home')],
            [__('Services'), route('services.index')],
            [__('Solutions'), route('solutions.index')],
            [__('Portfolio'), route('portfolio.index')],
            [__('Hosting'), route('hosting')],
            [__('Packages'), route('packages.index')],
            [__('About Us'), route('about')],
            [__('Blog'), route('blog.index')],
            [__('Contact'), route('contact.form')],
        ];

        return array_map(fn (array $item) => [
            'label' => $item[0],
            'url' => $item[1],
            'target' => null,
            'children' => [],
        ], $items);
    }
}
