@props(['items' => []])

@php
    /** @var array<int, array{label: string, url?: string|null}> $items */
    $crumbs = array_merge([['label' => __('Home'), 'url' => route('home')]], $items);

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $index) => array_filter([
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['label'],
            'item' => $crumb['url'] ?? null,
        ]))->all(),
    ];
@endphp

<nav aria-label="{{ __('Breadcrumb') }}" {{ $attributes->merge(['class' => 'text-sm']) }}>
    <ol class="flex flex-wrap items-center gap-1.5">
        @foreach ($crumbs as $crumb)
            <li class="flex items-center gap-1.5">
                @unless ($loop->first)
                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                @endunless
                @if (! $loop->last && ! empty($crumb['url']))
                    <a href="{{ $crumb['url'] }}" class="text-slate-500 hover:text-brand-600">{{ $crumb['label'] }}</a>
                @else
                    <span class="font-medium text-navy-900" @if ($loop->last) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@push('jsonld')
    <script type="application/ld+json">@json($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
@endpush
