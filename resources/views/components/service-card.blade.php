@props(['service'])

@php
    $image = $service->getFirstMediaUrl('featured', 'card');
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-lg']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $service->t('name') }}" loading="lazy" class="mb-5 h-40 w-full rounded-xl object-cover" />
    @else
        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-white" aria-hidden="true">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
            </svg>
        </div>
    @endif
    <h3 class="text-lg font-bold text-navy-900">
        <a href="{{ route('services.show', $service) }}" class="focus-visible:outline-none">
            <span class="absolute inset-0" aria-hidden="true"></span>
            {{ $service->t('name') }}
        </a>
    </h3>
    @if ($service->t('excerpt'))
        <p class="mt-2 text-sm leading-relaxed text-slate-600 line-clamp-3">{{ $service->t('excerpt') }}</p>
    @endif
    <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 group-hover:text-brand-700">
        {{ __('Learn more') }}
        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
        </svg>
    </span>
</article>
