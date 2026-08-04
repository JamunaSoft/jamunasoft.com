@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Web Hosting & Servers'),
        'subtitle' => __('Fast, secure and fully managed hosting infrastructure — from shared hosting to cloud servers.'),
        'breadcrumbs' => [['label' => __('Hosting')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($groups->isNotEmpty())
                <div x-data="{ tab: '{{ $groups->keys()->first() }}' }">
                    <div role="tablist" aria-label="{{ __('Hosting plan types') }}" class="flex flex-wrap justify-center gap-2">
                        @foreach ($groups as $key => $group)
                            <button
                                type="button"
                                role="tab"
                                id="tab-{{ $key }}"
                                aria-controls="panel-{{ $key }}"
                                :aria-selected="tab === '{{ $key }}' ? 'true' : 'false'"
                                @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'bg-navy-900 text-white' : 'bg-white text-slate-600 border border-slate-200 hover:border-brand-400 hover:text-brand-700'"
                                class="rounded-full px-5 py-2.5 text-sm font-semibold transition-colors"
                            >
                                {{ $group['type']->getLabel() }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($groups as $key => $group)
                        <div
                            x-show="tab === '{{ $key }}'"
                            x-cloak
                            role="tabpanel"
                            id="panel-{{ $key }}"
                            aria-labelledby="tab-{{ $key }}"
                            class="mt-10 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                        >
                            @foreach ($group['plans'] as $plan)
                                <x-hosting-plan-card :plan="$plan" />
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state :title="__('Hosting plans coming soon')" :description="__('Our hosting plans are being finalised. Contact us for a custom hosting quote.')">
                    <x-button :href="route('quote.create', ['service' => 'hosting'])" variant="primary" size="sm">{{ __('Request a Quote') }}</x-button>
                </x-empty-state>
            @endif
        </div>
    </div>

    {{-- Infrastructure services --}}
    <section class="bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-section-heading
                :eyebrow="__('Included & add-ons')"
                :title="__('Everything Your Infrastructure Needs')"
                :subtitle="__('Every hosting plan is backed by real engineers who manage, monitor and secure your servers.')"
                centered
            />
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['title' => __('Free SSL Certificates'), 'description' => __('HTTPS on every domain, renewed automatically — better security and better SEO.')],
                    ['title' => __('Daily Backups'), 'description' => __('Automatic off-site backups with fast restore, so one mistake never becomes a disaster.')],
                    ['title' => __('Website Migration'), 'description' => __('We move your existing websites and email to our servers free of charge, with zero downtime.')],
                    ['title' => __('Security Monitoring'), 'description' => __('Malware scanning, firewalls and continuous monitoring keep threats out before they cause harm.')],
                    ['title' => __('Server Management'), 'description' => __('Full VPS and dedicated server management — setup, hardening, patching and incident response.')],
                    ['title' => __('Business Email'), 'description' => __('Professional email on your own domain with spam protection and generous mailbox storage.')],
                ] as $item)
                    <div class="rounded-2xl border border-slate-200 p-6">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-white" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-navy-900">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <x-cta-section
        :heading="__('Not sure which plan fits?')"
        :description="__('Tell us about your website or application and we will recommend the right plan — no overselling.')"
    />
@endsection
