@props([
    'eyebrow' => null,
    'title',
    'subtitle' => null,
    'centered' => false,
    'light' => false,
])

<div {{ $attributes->merge(['class' => 'max-w-2xl mb-10 md:mb-14'.($centered ? ' mx-auto text-center' : '')]) }}>
    @if ($eyebrow)
        <p class="text-sm font-semibold uppercase tracking-widest {{ $light ? 'text-accent-400' : 'text-brand-600' }} mb-3">{{ $eyebrow }}</p>
    @endif
    <h2 class="text-3xl md:text-4xl font-bold tracking-tight {{ $light ? 'text-white' : 'text-navy-900' }}">{{ $title }}</h2>
    @if ($subtitle)
        <p class="mt-4 text-lg {{ $light ? 'text-slate-300' : 'text-slate-600' }}">{{ $subtitle }}</p>
    @endif
</div>
