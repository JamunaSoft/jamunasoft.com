@extends('layouts.app')

@section('content')
    @php
        $featuredImage = $portfolio->getFirstMediaUrl('featured', 'card');
        $gallery = $portfolio->getMedia('gallery');
        $clientLogo = $portfolio->getFirstMediaUrl('client_logo');
        $keyFeatures = is_array($portfolio->key_features) ? $portfolio->key_features : [];
        $technologies = is_array($portfolio->technologies) ? $portfolio->technologies : [];
        $results = is_array($portfolio->results) ? $portfolio->results : [];
    @endphp

    @include('partials.page-header', [
        'title' => $portfolio->t('title'),
        'subtitle' => $portfolio->t('summary'),
        'breadcrumbs' => [
            ['label' => __('Portfolio'), 'url' => route('portfolio.index')],
            ['label' => $portfolio->t('title')],
        ],
    ])

    <div class="bg-white py-16 lg:py-20">
        <div class="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <div class="lg:col-span-2">
                @if ($videoEmbed = $portfolio->videoEmbedUrl())
                    <div class="relative w-full overflow-hidden rounded-2xl shadow-sm" style="padding-top: 56.25%;">
                        <iframe
                            src="{{ $videoEmbed }}"
                            title="{{ $portfolio->t('title') }}"
                            class="absolute inset-0 h-full w-full"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            loading="lazy"
                        ></iframe>
                    </div>
                @elseif ($featuredImage)
                    <img src="{{ $featuredImage }}" alt="{{ $portfolio->t('title') }}" class="w-full rounded-2xl shadow-sm" />
                @else
                    <x-placeholder-image class="aspect-video w-full rounded-2xl" :label="$portfolio->t('title')" />
                @endif

                @if ($portfolio->challenge)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('The Challenge') }}</h2>
                    <div class="rich-text mt-4">{!! $portfolio->challenge !!}</div>
                @endif

                @if ($portfolio->solution)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Our Solution') }}</h2>
                    <div class="rich-text mt-4">{!! $portfolio->solution !!}</div>
                @endif

                @if ($keyFeatures)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Key Features') }}</h2>
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($keyFeatures as $feature)
                            @if (is_array($feature))
                                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                                    <h3 class="font-bold text-navy-900">{{ $feature['title'] ?? '' }}</h3>
                                    @if (! empty($feature['description']))
                                        <p class="mt-1.5 text-sm leading-relaxed text-slate-600">{{ $feature['description'] }}</p>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($results)
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Results') }}</h2>
                    <div class="mt-6 space-y-4">
                        @foreach ($results as $result)
                            @if (is_array($result))
                                <div class="flex items-start gap-3 rounded-2xl bg-emerald-50 p-5">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" /></svg>
                                    <div>
                                        <h3 class="font-bold text-navy-900">{{ $result['title'] ?? '' }}</h3>
                                        @if (! empty($result['description']))
                                            <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $result['description'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($gallery->isNotEmpty())
                    <h2 class="mt-12 text-2xl font-bold text-navy-900">{{ __('Screenshots') }}</h2>
                    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($gallery as $media)
                            <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-2xl border border-slate-200">
                                <img src="{{ $media->getUrl('card') ?: $media->getUrl() }}" alt="{{ __('Screenshot of :title', ['title' => $portfolio->t('title')]) }}" loading="lazy" class="w-full transition-transform duration-300 group-hover:scale-[1.02]" />
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($portfolio->testimonial_quote)
                    <figure class="mt-12 rounded-2xl bg-navy-950 p-8">
                        <svg class="h-8 w-8 text-accent-400" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
                        <blockquote class="mt-4 text-lg leading-relaxed text-white">{{ $portfolio->testimonial_quote }}</blockquote>
                        @if ($portfolio->testimonial_author)
                            <figcaption class="mt-4 text-sm font-semibold text-slate-300">— {{ $portfolio->testimonial_author }}</figcaption>
                        @endif
                    </figure>
                @endif
            </div>

            <aside class="space-y-8">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                    <h2 class="text-base font-bold text-navy-900">{{ __('Project details') }}</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        @if ($portfolio->client_name)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Client') }}</dt>
                                <dd class="font-semibold text-navy-900 text-right">{{ $portfolio->client_name }}</dd>
                            </div>
                        @endif
                        @if ($portfolio->category)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Category') }}</dt>
                                <dd class="font-semibold text-navy-900 text-right">{{ $portfolio->category->t('name') }}</dd>
                            </div>
                        @endif
                        @if ($portfolio->industry)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Industry') }}</dt>
                                <dd class="font-semibold text-navy-900 text-right">{{ $portfolio->industry }}</dd>
                            </div>
                        @endif
                        @if ($portfolio->completed_at)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">{{ __('Completed') }}</dt>
                                <dd class="font-semibold text-navy-900 text-right">{{ $portfolio->completed_at->format('M Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($clientLogo)
                        <img src="{{ $clientLogo }}" alt="{{ $portfolio->client_name }}" class="mt-5 max-h-12" />
                    @endif
                    @if ($portfolio->project_url)
                        <x-button :href="$portfolio->project_url" variant="outline" size="sm" class="mt-5 w-full" target="_blank" rel="nofollow noopener">
                            {{ __('Visit Live Project') }}
                        </x-button>
                    @endif
                </div>

                @if ($portfolio->services->isNotEmpty())
                    <div class="rounded-2xl border border-slate-200 p-6">
                        <h2 class="text-base font-bold text-navy-900">{{ __('Services provided') }}</h2>
                        <ul class="mt-4 space-y-2.5">
                            @foreach ($portfolio->services as $service)
                                <li>
                                    <a href="{{ route('services.show', $service) }}" class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline">{{ $service->t('name') }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($technologies)
                    <div class="rounded-2xl border border-slate-200 p-6">
                        <h2 class="text-base font-bold text-navy-900">{{ __('Technologies used') }}</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($technologies as $technology)
                                <li><x-badge>{{ is_array($technology) ? ($technology['name'] ?? '') : $technology }}</x-badge></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="rounded-2xl bg-navy-950 p-6 text-center">
                    <h2 class="text-lg font-bold text-white">{{ __('Want something similar?') }}</h2>
                    <p class="mt-2 text-sm text-slate-300">{{ __('Tell us about your project and get a free quotation.') }}</p>
                    <x-button :href="route('quote.create')" variant="primary" size="sm" class="mt-5 w-full">{{ __('Request a Quotation') }}</x-button>
                </div>
            </aside>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('More work')" :title="__('Related Projects')" />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $relatedPortfolio)
                        <x-portfolio-card :portfolio="$relatedPortfolio" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-section />
@endsection
