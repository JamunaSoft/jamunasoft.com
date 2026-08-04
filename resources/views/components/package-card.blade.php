@props(['package'])

@php
    $features = is_array($package->features) ? $package->features : [];
    $excluded = is_array($package->excluded_features) ? $package->excluded_features : [];
    $ctaUrl = $package->cta_url ?: route('quote.create', ['package' => $package->slug]);
    $hasDiscount = $package->discounted_price !== null && $package->price !== null && (float) $package->discounted_price < (float) $package->price;
@endphp

<article {{ $attributes->merge(['class' => 'relative flex flex-col rounded-2xl border bg-white p-8 shadow-sm '.($package->is_recommended ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200')]) }}>
    @if ($package->is_recommended)
        <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500 px-4 py-1 text-xs font-bold text-white">{{ __('Recommended') }}</span>
    @endif

    <h3 class="text-lg font-bold text-navy-900">{{ $package->t('name') }}</h3>
    @if ($package->t('excerpt'))
        <p class="mt-2 text-sm text-slate-600">{{ $package->t('excerpt') }}</p>
    @endif

    <div class="mt-6">
        @if ($package->is_starting_from)
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('Starting from') }}</p>
        @endif
        <p class="flex items-baseline gap-2">
            @if ($package->displayPrice())
                <span class="text-4xl font-bold text-navy-900">&#2547;{{ $package->displayPrice() }}</span>
                @if ($hasDiscount)
                    <span class="text-base font-medium text-slate-400 line-through">&#2547;{{ number_format((float) $package->price) }}</span>
                @endif
                @if ($package->price_suffix)
                    <span class="text-sm text-slate-500">{{ $package->t('price_suffix') }}</span>
                @endif
            @else
                <span class="text-2xl font-bold text-navy-900">{{ __('Custom pricing') }}</span>
            @endif
        </p>
    </div>

    @if ($features || $excluded)
        <ul class="mt-6 space-y-2.5 text-sm">
            @foreach ($features as $feature)
                <li class="flex items-start gap-2 text-slate-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span>{{ is_array($feature) ? ($feature['label'] ?? '') : $feature }}</span>
                </li>
            @endforeach
            @foreach ($excluded as $feature)
                <li class="flex items-start gap-2 text-slate-400">
                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span class="line-through">{{ is_array($feature) ? ($feature['label'] ?? '') : $feature }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-6 space-y-1.5 text-xs text-slate-500">
        @if ($package->delivery_time)
            <p>{{ __('Delivery') }}: <span class="font-medium text-slate-700">{{ $package->t('delivery_time') }}</span></p>
        @endif
        @if ($package->support_period)
            <p>{{ __('Support') }}: <span class="font-medium text-slate-700">{{ $package->t('support_period') }}</span></p>
        @endif
    </div>

    <div class="mt-8 pt-2 mt-auto">
        <x-button :href="$ctaUrl" :variant="$package->is_recommended ? 'primary' : 'outline'" class="w-full">
            {{ $package->cta_label ? $package->t('cta_label') : __('Get Started') }}
        </x-button>
    </div>
</article>
