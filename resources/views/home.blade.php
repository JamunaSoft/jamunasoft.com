@extends('layouts.app')

@section('content')
    @php
        $heroImagePath = settings('hero_image_path');
        $heroImageUrl = $heroImagePath ? \Illuminate\Support\Facades\Storage::disk('public')->url($heroImagePath) : null;
        $heroBadges = settings('hero_badges');
        $stats = settings('stats');
        $whyUs = settings_t('why_us', settings('why_us'));
        $processSteps = settings_t('process_steps', settings('process_steps'));
        $whatsappDigits = preg_replace('/\D+/', '', (string) settings('whatsapp_number', ''));
    @endphp

    {{-- Hero --}}
    <section class="relative overflow-hidden bg-navy-950">
        <div class="absolute inset-0" aria-hidden="true">
            <div class="absolute -top-40 -right-40 h-[32rem] w-[32rem] rounded-full bg-brand-600/30 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-40 h-[32rem] w-[32rem] rounded-full bg-accent-500/20 blur-3xl"></div>
        </div>
        <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-28">
            <div>
                <h1 class="text-4xl font-bold tracking-tight text-white md:text-5xl lg:text-[3.4rem] lg:leading-[1.1]">
                    {{ settings_t('hero_heading', __('Software, websites & digital growth for your business')) }}
                </h1>
                <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-300">
                    {{ settings_t('hero_subheading', __('Jamuna Soft builds fast, reliable websites, custom software, hosting and digital marketing that help Bangladeshi businesses grow.')) }}
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <x-button :href="settings('hero_primary_cta_url', route('quote.create'))" variant="primary" size="lg">
                        {{ settings_t('hero_primary_cta_label', __('Request a Quotation')) }}
                    </x-button>
                    <x-button :href="settings('hero_secondary_cta_url', route('portfolio.index'))" variant="ghost-light" size="lg">
                        {{ settings_t('hero_secondary_cta_label', __('See Our Work')) }}
                    </x-button>
                </div>
                @if (is_array($heroBadges) && $heroBadges)
                    <ul class="mt-8 flex flex-wrap gap-2">
                        @foreach ($heroBadges as $badge)
                            <li><x-badge color="light">{{ is_array($badge) ? ($badge['label'] ?? '') : $badge }}</x-badge></li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="relative hidden lg:block">
                @if ($heroImageUrl)
                    <img src="{{ $heroImageUrl }}" alt="" class="w-full rounded-2xl shadow-2xl shadow-navy-900/50" />
                @else
                    <x-placeholder-image class="aspect-[4/3] w-full rounded-2xl shadow-2xl shadow-navy-900/50" :label="settings('company_name', 'Jamuna Soft')" />
                @endif
            </div>
        </div>
    </section>

    {{-- Stats --}}
    @if (is_array($stats) && $stats)
        <section class="border-b border-slate-100 bg-white">
            <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
                @foreach ($stats as $stat)
                    @if (is_array($stat))
                        <x-stat :value="$stat['value'] ?? ''" :label="$stat['label'] ?? ''" />
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    {{-- Services --}}
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-section-heading
                :eyebrow="__('What we do')"
                :title="__('Our Services')"
                :subtitle="__('End-to-end digital services — from idea to launch and beyond.')"
                centered
            />
            @if ($services->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $service)
                        <x-service-card :service="$service" />
                    @endforeach
                </div>
                <div class="mt-10 text-center">
                    <x-button :href="route('services.index')" variant="outline">{{ __('View all services') }}</x-button>
                </div>
            @else
                <x-empty-state :title="__('Services coming soon')" :description="__('We are preparing our service catalogue. Contact us to discuss what you need.')">
                    <x-button :href="route('contact.form')" variant="primary" size="sm">{{ __('Contact') }}</x-button>
                </x-empty-state>
            @endif
        </div>
    </section>

    {{-- Solutions --}}
    @if ($solutions->isNotEmpty())
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('Industries')"
                    :title="__('Solutions for Your Industry')"
                    :subtitle="__('Purpose-built solutions for the sectors we know best.')"
                    centered
                />
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($solutions as $solution)
                        <a href="{{ route('solutions.show', $solution) }}" class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-shadow hover:shadow-lg">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600" aria-hidden="true">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                            </span>
                            <h3 class="mt-4 text-base font-bold text-navy-900 group-hover:text-brand-700">{{ $solution->t('name') }}</h3>
                            @if ($solution->t('excerpt'))
                                <p class="mt-2 text-sm text-slate-600 line-clamp-2">{{ $solution->t('excerpt') }}</p>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Portfolio --}}
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-section-heading
                :eyebrow="__('Our work')"
                :title="__('Featured Projects')"
                :subtitle="__('A selection of projects we are proud of.')"
                centered
            />
            @if ($portfolios->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($portfolios as $portfolio)
                        <x-portfolio-card :portfolio="$portfolio" />
                    @endforeach
                </div>
                <div class="mt-10 text-center">
                    <x-button :href="route('portfolio.index')" variant="outline">{{ __('View full portfolio') }}</x-button>
                </div>
            @else
                <x-empty-state :title="__('Case studies coming soon')" :description="__('We are writing up our recent projects. Check back shortly.')" />
            @endif
        </div>
    </section>

    {{-- Why us --}}
    @if (is_array($whyUs) && $whyUs)
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('Why Jamuna Soft')"
                    :title="__('Why Businesses Choose Us')"
                    centered
                />
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

    {{-- Process --}}
    @if (is_array($processSteps) && $processSteps)
        <section class="bg-navy-950 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('How we work')"
                    :title="__('Our Work Process')"
                    centered
                    light
                />
                <ol class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($processSteps as $step)
                        @if (is_array($step))
                            <li class="rounded-2xl border border-white/10 bg-white/5 p-6">
                                <span class="text-3xl font-bold text-gradient">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3 class="mt-3 text-base font-bold text-white">{{ $step['title'] ?? '' }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $step['description'] ?? '' }}</p>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- Packages --}}
    <section class="bg-slate-50 py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <x-section-heading
                :eyebrow="__('Pricing')"
                :title="__('Popular Packages')"
                :subtitle="__('Clear, honest pricing with no hidden costs.')"
                centered
            />
            @if ($packages->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ($packages as $package)
                        <x-package-card :package="$package" />
                    @endforeach
                </div>
                <div class="mt-10 text-center">
                    <x-button :href="route('packages.index')" variant="outline">{{ __('View all packages') }}</x-button>
                </div>
            @else
                <x-empty-state :title="__('Packages coming soon')" :description="__('Our pricing packages are being finalised. Request a custom quotation in the meantime.')">
                    <x-button :href="route('quote.create')" variant="primary" size="sm">{{ __('Request a Quotation') }}</x-button>
                </x-empty-state>
            @endif
        </div>
    </section>

    {{-- Domain search --}}
    @include('partials.domain-search')

    {{-- Client logos --}}
    @if ($clientLogos->isNotEmpty())
        @include('partials.client-logos')
    @endif

    {{-- Testimonials --}}
    @if ($testimonials->isNotEmpty())
        <section class="bg-white py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('Testimonials')"
                    :title="__('What Our Clients Say')"
                    centered
                />
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($testimonials as $testimonial)
                        <x-testimonial-card :testimonial="$testimonial" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Blog --}}
    @if ($posts->isNotEmpty())
        <section class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('Insights')"
                    :title="__('Latest from the Blog')"
                    centered
                />
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ($posts as $post)
                        <x-blog-card :post="$post" />
                    @endforeach
                </div>
                <div class="mt-10 text-center">
                    <x-button :href="route('blog.index')" variant="outline">{{ __('Read more articles') }}</x-button>
                </div>
            </div>
        </section>
    @endif

    {{-- FAQ --}}
    @if ($faqs->isNotEmpty())
        <section class="bg-white py-20">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <x-section-heading
                    :eyebrow="__('FAQ')"
                    :title="__('Frequently Asked Questions')"
                    centered
                />
                <x-faq-accordion :faqs="$faqs" jsonld />
            </div>
        </section>
    @endif

    <x-cta-section />
@endsection
