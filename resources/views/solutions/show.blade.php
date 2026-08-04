@extends('layouts.app')

@section('content')
    @php
        $challenges = is_array($solution->challenges) ? $solution->challenges : [];
        $offerings = is_array($solution->offerings) ? $solution->offerings : [];
        $benefits = is_array($solution->benefits) ? $solution->benefits : [];
    @endphp

    @include('partials.page-header', [
        'title' => $solution->t('name'),
        'subtitle' => $solution->t('excerpt'),
        'breadcrumbs' => [
            ['label' => __('Solutions'), 'url' => route('solutions.index')],
            ['label' => $solution->t('name')],
        ],
    ])

    <div class="bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($solution->t('description'))
                <div class="rich-text max-w-3xl">
                    {!! $solution->t('description') !!}
                </div>
            @endif

            @if ($challenges || $offerings)
                <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-2">
                    @if ($challenges)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-8">
                            <h2 class="text-xl font-bold text-navy-900">{{ __('Common challenges') }}</h2>
                            <ul class="mt-6 space-y-4">
                                @foreach ($challenges as $challenge)
                                    @if (is_array($challenge))
                                        <li class="flex items-start gap-3">
                                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                            <div>
                                                <h3 class="font-semibold text-navy-900">{{ $challenge['title'] ?? '' }}</h3>
                                                @if (! empty($challenge['description']))
                                                    <p class="mt-1 text-sm leading-relaxed text-slate-600">{{ $challenge['description'] }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($offerings)
                        <div class="rounded-2xl bg-navy-950 p-8">
                            <h2 class="text-xl font-bold text-white">{{ __('How Jamuna Soft helps') }}</h2>
                            <ul class="mt-6 space-y-4">
                                @foreach ($offerings as $offering)
                                    @if (is_array($offering))
                                        <li class="flex items-start gap-3">
                                            <svg class="mt-0.5 h-5 w-5 shrink-0 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            <div>
                                                <h3 class="font-semibold text-white">{{ $offering['title'] ?? '' }}</h3>
                                                @if (! empty($offering['description']))
                                                    <p class="mt-1 text-sm leading-relaxed text-slate-400">{{ $offering['description'] }}</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            @if ($benefits)
                <h2 class="mt-16 text-2xl font-bold text-navy-900">{{ __('Benefits') }}</h2>
                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($benefits as $benefit)
                        @if (is_array($benefit))
                            <div class="rounded-2xl border border-slate-200 p-6">
                                <h3 class="font-bold text-navy-900">{{ $benefit['title'] ?? '' }}</h3>
                                @if (! empty($benefit['description']))
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $benefit['description'] }}</p>
                                @endif
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if ($solution->services->isNotEmpty())
        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('Recommended')" :title="__('Recommended Services')" />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($solution->services as $service)
                        <x-service-card :service="$service" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($relatedPortfolios->isNotEmpty())
        <section class="bg-white py-16">
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
@endsection
