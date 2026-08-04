@props(['plan'])

@php
    $features = is_array($plan->features) ? $plan->features : [];
    $specs = array_filter([
        __('Storage') => $plan->storage,
        __('Bandwidth') => $plan->bandwidth,
        __('Websites') => $plan->websites,
        __('Email Accounts') => $plan->email_accounts,
        __('Databases') => $plan->databases,
        __('Backups') => $plan->backup_frequency,
        __('Support') => $plan->support_level,
    ]);
@endphp

<article {{ $attributes->merge(['class' => 'relative flex flex-col rounded-2xl border bg-white p-8 shadow-sm '.($plan->is_recommended ? 'border-brand-500 ring-2 ring-brand-500/20' : 'border-slate-200')]) }}>
    @if ($plan->is_recommended)
        <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500 px-4 py-1 text-xs font-bold text-white">{{ __('Recommended') }}</span>
    @endif

    <h3 class="text-lg font-bold text-navy-900">{{ $plan->t('name') }}</h3>

    <div class="mt-4">
        @if ($plan->monthly_price !== null)
            <p class="flex items-baseline gap-1.5">
                <span class="text-4xl font-bold text-navy-900">&#2547;{{ number_format((float) $plan->monthly_price) }}</span>
                <span class="text-sm text-slate-500">/{{ __('month') }}</span>
            </p>
        @endif
        @if ($plan->yearly_price !== null)
            <p class="mt-1 text-sm text-slate-500">&#2547;{{ number_format((float) $plan->yearly_price) }} /{{ __('year') }}</p>
        @endif
        @if ($plan->monthly_price === null && $plan->yearly_price === null)
            <p class="text-2xl font-bold text-navy-900">{{ __('Custom pricing') }}</p>
        @endif
    </div>

    @if ($specs)
        <dl class="mt-6 space-y-2 border-t border-slate-100 pt-5 text-sm">
            @foreach ($specs as $label => $value)
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500">{{ $label }}</dt>
                    <dd class="font-medium text-navy-900 text-right">{{ $value }}</dd>
                </div>
            @endforeach
            <div class="flex items-center justify-between gap-4">
                <dt class="text-slate-500">{{ __('Free SSL') }}</dt>
                <dd class="font-medium {{ $plan->has_ssl ? 'text-emerald-600' : 'text-slate-400' }}">{{ $plan->has_ssl ? __('Included') : __('Not included') }}</dd>
            </div>
        </dl>
    @endif

    @if ($features)
        <ul class="mt-5 space-y-2 text-sm">
            @foreach ($features as $feature)
                <li class="flex items-start gap-2 text-slate-700">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    <span>{{ is_array($feature) ? ($feature['label'] ?? '') : $feature }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="mt-8 pt-2 mt-auto">
        <x-button :href="route('quote.create', ['service' => 'hosting', 'plan' => $plan->name])" :variant="$plan->is_recommended ? 'primary' : 'outline'" class="w-full">
            {{ __('Order Now') }}
        </x-button>
    </div>
</article>
