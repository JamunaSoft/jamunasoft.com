@extends('layouts.app')

@section('content')
    @php
        $featuredImage = $service->getFirstMediaUrl('featured', 'card');
        $benefits = is_array($service->benefits) ? $service->benefits : [];
        $features = is_array($service->features) ? $service->features : [];
        $technologies = is_array($service->technologies) ? $service->technologies : [];
        $processSteps = is_array($service->process_steps) ? $service->process_steps : [];
    @endphp

    @include('partials.page-header', [
        'title' => $service->t('name'),
        'subtitle' => $service->t('excerpt'),
        'breadcrumbs' => [
            ['label' => __('Services'), 'url' => route('services.index')],
            ['label' => $service->t('name')],
        ],
    ])

    <div class="bg-white py-16 lg:py-20">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="lg:col-span-2">
                @if ($featuredImage)
                    <img src="{{ $featuredImage }}" alt="{{ $service->t('name') }}" class="mb-8 w-full rounded-2xl shadow-sm" />
                @endif

                @if ($service->t('description'))
                    <div class="rich-text">
                        {!! $service->t('description') !!}
                    </div>
                @endif

                @if ($benefits)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Benefits') }}</h2>
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($benefits as $benefit)
                            @if (is_array($benefit))
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <h3 class="font-bold text-navy-900">{{ $benefit['title'] ?? '' }}</h3>
                                    @if (! empty($benefit['description']))
                                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $benefit['description'] }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($features && $features !== $benefits)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('What\'s included') }}</h2>
                    <ul class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($features as $feature)
                            @if (is_array($feature))
                                <li class="flex items-start gap-2.5">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="text-slate-700">{{ $feature['title'] ?? '' }}</span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @endif

                @if ($processSteps)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('How we deliver') }}</h2>
                    <ol class="mt-6 space-y-4">
                        @foreach ($processSteps as $step)
                            @if (is_array($step))
                                <li class="flex gap-4 rounded-2xl border border-slate-200 p-5">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white">{{ $loop->iteration }}</span>
                                    <div>
                                        <h3 class="font-bold text-navy-900">{{ $step['title'] ?? '' }}</h3>
                                        @if (! empty($step['description']))
                                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $step['description'] }}</p>
                                        @endif
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ol>
                @endif

                @if ($service->faqs->isNotEmpty())
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Frequently Asked Questions') }}</h2>
                    <x-faq-accordion :faqs="$service->faqs" jsonld class="mt-6" />
                @endif
            </div>

            <aside class="space-y-8">
                @if ($technologies)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <h2 class="text-base font-bold text-navy-900">{{ __('Technologies we use') }}</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($technologies as $technology)
                                <li><x-badge>{{ is_array($technology) ? ($technology['name'] ?? '') : $technology }}</x-badge></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($relatedPackages->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 p-6">
                        <h2 class="text-base font-bold text-navy-900">{{ __('Related packages') }}</h2>
                        <ul class="mt-4 space-y-3">
                            @foreach ($relatedPackages as $package)
                                <li class="flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-slate-700">{{ $package->t('name') }}</span>
                                    @if ($package->displayPrice())
                                        <span class="font-bold text-brand-700">৳{{ $package->displayPrice() }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                        <x-button :href="route('packages.index')" variant="outline" size="sm" class="mt-5 w-full">{{ __('View all packages') }}</x-button>
                    </div>
                @endif

                <div class="rounded-2xl bg-navy-950 p-6 text-center">
                    <h2 class="text-lg font-bold text-white">{{ __('Have a project in mind?') }}</h2>
                    <p class="mt-2 text-sm text-slate-300">{{ __('Get a free consultation and a clear quotation within one business day.') }}</p>
                    <x-button :href="route('quote.create', ['service' => $service->slug])" variant="primary" size="sm" class="mt-5 w-full">{{ __('Request a Quotation') }}</x-button>
                </div>
            </aside>
        </div>
    </div>

    @if ($relatedPortfolios->isNotEmpty())
        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('Our work')" :title="__('Related Projects')" />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedPortfolios as $portfolio)
                        <x-portfolio-card :portfolio="$portfolio" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-section />

    @push('jsonld')
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Service',
                'name' => $service->t('name'),
                'description' => str(strip_tags((string) $service->t('excerpt')))->limit(200)->toString(),
                'url' => route('services.show', $service),
                'provider' => [
                    '@type' => 'Organization',
                    'name' => settings('company_name', config('app.name')),
                    'url' => url('/'),
                ],
                'areaServed' => 'Bangladesh',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush
@endsection
