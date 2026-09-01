@props(['portfolio'])

@php
    $image = $portfolio->getFirstMediaUrl('featured', 'card');
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-lg']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $portfolio->t('title') }}" loading="lazy" class="h-52 w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]" />
    @else
        <x-placeholder-image class="h-52 w-full" />
    @endif
    @if ($portfolio->video_url)
        <span class="pointer-events-none absolute inset-x-0 top-0 flex h-52 items-center justify-center" aria-hidden="true">
            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-navy-950/60 text-white backdrop-blur-sm transition-colors group-hover:bg-brand-600">
                <svg viewBox="0 0 24 24" fill="currentColor" class="ml-1 h-6 w-6"><path d="M8 5v14l11-7z" /></svg>
            </span>
        </span>
    @endif
    <div class="flex flex-1 flex-col p-6">
        <div class="flex flex-wrap items-center gap-2">
            @if ($portfolio->category)
                <x-badge>{{ $portfolio->category->t('name') }}</x-badge>
            @endif
            @if ($portfolio->industry)
                <x-badge color="slate">{{ $portfolio->t('industry') }}</x-badge>
            @endif
        </div>
        <h3 class="mt-3 text-lg font-bold text-navy-900">
            <a href="{{ route('portfolio.show', $portfolio) }}" class="focus-visible:outline-none">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $portfolio->t('title') }}
            </a>
        </h3>
        @if ($portfolio->t('summary'))
            <p class="mt-2 text-sm leading-relaxed text-slate-600 line-clamp-2">{{ $portfolio->t('summary') }}</p>
        @endif
        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 group-hover:text-brand-700">
            {{ __('View case study') }}
            <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </div>
</article>
