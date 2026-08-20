@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Domain Registration'),
        'subtitle' => __('Find your perfect domain name — instant search, local payment, and friendly support in Bangla.'),
        'breadcrumbs' => [['label' => __('Domains')]],
    ])

    <div
        class="bg-slate-50 py-16 lg:py-20"
        x-data="{
            orderDomain: '{{ old('domain', '') }}',
            orderPrice: {{ old('domain') ? (float) (\App\Models\Tld::matching((string) old('domain'))?->register_price ?? 0) : 0 }},
            years: {{ (int) old('years', 1) }},
        }"
    >
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            {{-- Search --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                <h2 class="text-lg font-bold text-navy-900">{{ __('Search for a domain') }}</h2>
                <form method="GET" action="{{ route('domains.index') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="q"
                        value="{{ $query }}"
                        placeholder="{{ __('yourbusiness or yourbusiness.com') }}"
                        required
                        class="w-full rounded-xl border-slate-300 px-4 py-3 text-base focus:border-brand-500 focus:ring-brand-500"
                    />
                    <x-button type="submit" variant="primary" size="lg" class="shrink-0">{{ __('Search') }}</x-button>
                </form>

                @if ($searchError)
                    <x-alert type="warning" class="mt-5">{{ $searchError }}</x-alert>
                @endif

                {{-- Results --}}
                @if ($results !== null && ! $searchError)
                    <ul class="mt-6 divide-y divide-slate-100">
                        @forelse ($results as $result)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-4">
                                <span class="font-semibold text-navy-900">{{ $result['domain'] }}</span>
                                @if ($result['tld'] === null)
                                    <span class="text-sm text-slate-500">{{ __('Extension not offered — contact us') }}</span>
                                @elseif ($result['premium'])
                                    <span class="flex items-center gap-3">
                                        <span class="text-sm font-semibold text-amber-600">{{ __('Premium domain') }}</span>
                                        <x-button :href="route('contact.form')" variant="outline" size="sm">{{ __('Contact us') }}</x-button>
                                    </span>
                                @elseif ($result['available'])
                                    <span class="flex items-center gap-3">
                                        <span class="text-sm font-bold text-emerald-600">৳{{ number_format($result['price'], 0) }}<span class="font-normal text-slate-500">/{{ __('yr') }}</span></span>
                                        <x-button
                                            type="button"
                                            variant="primary"
                                            size="sm"
                                            x-on:click="orderDomain = '{{ $result['domain'] }}'; orderPrice = {{ (float) $result['price'] }}; $nextTick(() => document.getElementById('order-form').scrollIntoView({behavior: 'smooth'}))"
                                        >
                                            {{ __('Order Now') }}
                                        </x-button>
                                    </span>
                                @else
                                    <span class="text-sm font-semibold text-rose-500">{{ __('Taken') }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="py-4 text-sm text-slate-500">{{ __('No results — try a different name.') }}</li>
                        @endforelse
                    </ul>
                @endif
            </div>

            {{-- Order form --}}
            <div id="order-form" x-show="orderDomain !== ''" x-cloak class="mt-8 rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                <h2 class="text-lg font-bold text-navy-900">
                    {{ __('Order') }} <span class="text-brand-700" x-text="orderDomain"></span>
                </h2>

                @if ($errors->any())
                    <x-alert type="error" class="mt-5">{{ $errors->first() }}</x-alert>
                @endif

                <form method="POST" action="{{ route('domains.order') }}" class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    @csrf

                    {{-- Honeypot --}}
                    <div class="hp-field" aria-hidden="true">
                        <label for="website_url_hp">Website</label>
                        <input type="text" id="website_url_hp" name="website_url_hp" tabindex="-1" autocomplete="off" />
                    </div>

                    <input type="hidden" name="domain" :value="orderDomain" />

                    <x-form.input name="name" :label="__('Full Name')" required autocomplete="name" />
                    <x-form.input name="email" :label="__('Email')" type="email" required autocomplete="email" />
                    <x-form.input name="phone" :label="__('Phone')" type="tel" autocomplete="tel" />

                    <div>
                        <label for="years" class="mb-1.5 block text-sm font-semibold text-navy-900">{{ __('Registration Period') }}</label>
                        <select
                            id="years"
                            name="years"
                            x-model.number="years"
                            class="w-full rounded-xl border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                        >
                            @foreach (range(1, 5) as $y)
                                <option value="{{ $y }}">{{ $y }} {{ __(str('year')->plural($y)->toString()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-5 py-4 sm:col-span-2">
                        <span class="text-sm font-semibold text-navy-900">{{ __('Total') }}</span>
                        <span class="text-lg font-bold text-navy-900">৳<span x-text="(orderPrice * years).toLocaleString()"></span></span>
                    </div>

                    <p class="text-xs leading-relaxed text-slate-500 sm:col-span-2">
                        {{ __('After placing the order you will receive payment instructions (bKash / bank transfer). Your domain is activated as soon as we confirm the payment.') }}
                    </p>

                    <div class="sm:col-span-2">
                        <x-button type="submit" variant="primary" size="lg">{{ __('Place Order') }}</x-button>
                    </div>
                </form>
            </div>

            {{-- TLD price table --}}
            @if ($tlds->isNotEmpty())
                <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                    <h2 class="text-lg font-bold text-navy-900">{{ __('Domain Prices') }}</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                    <th class="py-2.5 pr-4">{{ __('Extension') }}</th>
                                    <th class="py-2.5 pr-4">{{ __('Registration') }}</th>
                                    <th class="py-2.5 pr-4">{{ __('Renewal') }}</th>
                                    <th class="py-2.5">{{ __('Transfer') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tlds as $tld)
                                    <tr>
                                        <td class="py-3 pr-4 font-bold text-navy-900">.{{ $tld->tld }}</td>
                                        <td class="py-3 pr-4 text-slate-600">৳{{ number_format((float) $tld->register_price, 0) }}/{{ __('yr') }}</td>
                                        <td class="py-3 pr-4 text-slate-600">৳{{ number_format((float) $tld->renew_price, 0) }}/{{ __('yr') }}</td>
                                        <td class="py-3 text-slate-600">৳{{ number_format((float) $tld->transfer_price, 0) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="mt-8">
                    <x-empty-state :title="__('Domain pricing coming soon')" :description="__('We are finalising our domain prices. Contact us to order a domain today.')">
                        <x-button :href="route('contact.form')" variant="primary" size="sm">{{ __('Contact Us') }}</x-button>
                    </x-empty-state>
                </div>
            @endif
        </div>
    </div>
@endsection
