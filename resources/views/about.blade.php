@extends('layouts.app')

@section('content')
    @php
        $stats = settings('stats');
        $whyUs = settings_t('why_us', settings('why_us'));
        $coreValues = settings_t('core_values', settings('core_values'));
        $brandStoryPoints = settings_t('brand_story_points', settings('brand_story_points'));
        $brandMeaning = settings_t('brand_meaning');
        $logoPath = settings('logo_path');
        $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
    @endphp

    @include('partials.page-header', [
        'title' => __('About Jamuna Soft'),
        'subtitle' => settings_t('tagline'),
        'breadcrumbs' => [['label' => __('About Us')]],
    ])

    {{-- Intro & story --}}
    <section class="bg-white py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-12 lg:grid-cols-2">
                <div>
                    <x-section-heading :eyebrow="__('Who we are')" :title="__('Your Technology & Digital Growth Partner')" />
                    @if (settings_t('about_intro'))
                        <p class="text-lg leading-relaxed text-slate-600">{{ settings_t('about_intro') }}</p>
                    @endif
                    @if (settings_t('about_story'))
                        <p class="mt-6 leading-relaxed text-slate-600">{{ settings_t('about_story') }}</p>
                    @endif
                </div>
                <div class="space-y-6">
                    @if (settings_t('mission'))
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-7">
                            <h3 class="text-lg font-bold text-navy-900">{{ __('Our Mission') }}</h3>
                            <p class="mt-2 leading-relaxed text-slate-600">{{ settings_t('mission') }}</p>
                        </div>
                    @endif
                    @if (settings_t('vision'))
                        <div class="rounded-2xl bg-navy-950 p-7">
                            <h3 class="text-lg font-bold text-white">{{ __('Our Vision') }}</h3>
                            <p class="mt-2 leading-relaxed text-slate-300">{{ settings_t('vision') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Brand story --}}
    @if ((is_array($brandStoryPoints) && $brandStoryPoints) || $brandMeaning)
        <section class="bg-slate-50 py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('Our brand')"
                    :title="__('The Story Behind Our Logo')"
                    :subtitle="settings_t('brand_story_intro')"
                    centered
                />

                @if ($logoUrl)
                    <div class="mb-12 flex justify-center">
                        <div class="rounded-2xl border border-slate-200 bg-white px-12 py-8 shadow-sm">
                            <img src="{{ $logoUrl }}" alt="{{ settings('company_name', 'Jamuna Soft') }}" class="h-16 w-auto" />
                        </div>
                    </div>
                @endif

                @if (is_array($brandStoryPoints) && $brandStoryPoints)
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($brandStoryPoints as $point)
                            @if (is_array($point))
                                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                    <h3 class="text-base font-bold text-navy-900">{{ $point['title'] ?? '' }}</h3>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $point['description'] ?? '' }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($brandMeaning)
                    <figure class="mx-auto mt-12 max-w-3xl rounded-2xl bg-navy-950 p-8 text-center lg:p-10">
                        <blockquote class="text-xl font-semibold leading-relaxed text-white lg:text-2xl">
                            &ldquo;{{ $brandMeaning }}&rdquo;
                        </blockquote>
                        <figcaption class="mt-4 text-sm font-semibold uppercase tracking-widest text-accent-400">
                            {{ settings('company_name', 'Jamuna Soft') }}
                        </figcaption>
                    </figure>
                @endif
            </div>
        </section>
    @endif

    {{-- Stats --}}
    @if (is_array($stats) && $stats)
        <section class="border-y border-slate-100 bg-slate-50">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
                @foreach ($stats as $stat)
                    @if (is_array($stat))
                        <x-stat :value="$stat['value'] ?? ''" :label="$stat['label'] ?? ''" />
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    {{-- Core values --}}
    @if (is_array($coreValues) && $coreValues)
        <section class="bg-white py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('What drives us')" :title="__('Our Core Values')" centered />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($coreValues as $value)
                        @if (is_array($value))
                            <div class="rounded-2xl border border-slate-200 p-6 text-center">
                                <h3 class="text-base font-bold text-navy-900">{{ $value['title'] ?? '' }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $value['description'] ?? '' }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Why us --}}
    @if (is_array($whyUs) && $whyUs)
        <section class="bg-slate-50 py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('Why Jamuna Soft')" :title="__('Why Businesses Choose Us')" centered />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($whyUs as $item)
                        @if (is_array($item))
                            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-white" aria-hidden="true">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                </span>
                                <h3 class="mt-4 text-base font-bold text-navy-900">{{ $item['title'] ?? '' }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $item['description'] ?? '' }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Team --}}
    @if ($team->isNotEmpty())
        <section class="bg-white py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('Our people')" :title="__('Meet the Team')" centered />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($team as $member)
                        <x-team-card :member="$member" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="bg-slate-50 py-16 lg:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('Testimonials')" :title="__('What Our Clients Say')" centered />
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @foreach ($testimonials as $testimonial)
                        <x-testimonial-card :testimonial="$testimonial" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-section />
@endsection
