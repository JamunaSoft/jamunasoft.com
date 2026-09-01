{{-- Client logo marquee: pure-CSS infinite scroll, pauses on hover,
     falls back to a static wrapped row for reduced-motion users. --}}
<style>
    .client-marquee { overflow: hidden; position: relative; }
    .client-marquee__track {
        display: flex;
        width: max-content;
        animation: client-marquee-scroll 40s linear infinite;
    }
    .client-marquee:hover .client-marquee__track { animation-play-state: paused; }
    @keyframes client-marquee-scroll {
        to { transform: translateX(-50%); }
    }
    @media (prefers-reduced-motion: reduce) {
        .client-marquee__track { animation: none; flex-wrap: wrap; justify-content: center; width: auto; }
        .client-marquee__half--clone { display: none; }
    }
</style>

<section class="bg-slate-50 py-14" aria-label="{{ __('Our clients') }}">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-section-heading
            :eyebrow="__('Our Clients')"
            :title="settings_t('clients_heading', __('Trusted by Businesses Across Bangladesh'))"
            centered
        />
    </div>
    <div class="client-marquee mt-2">
        <div class="client-marquee__track">
            @foreach ([false, true] as $isClone)
                <div class="client-marquee__half {{ $isClone ? 'client-marquee__half--clone' : '' }} flex" @if ($isClone) aria-hidden="true" @endif>
                    @foreach ($clientLogos as $portfolio)
                        <a
                            href="{{ route('portfolio.show', $portfolio) }}"
                            class="group mx-6 flex w-32 shrink-0 flex-col items-center gap-3 py-2 sm:w-36"
                            @if ($isClone) tabindex="-1" @endif
                        >
                            <img
                                src="{{ $portfolio->getFirstMediaUrl('client_logo') }}"
                                alt="{{ $portfolio->client_name ?: $portfolio->t('title') }}"
                                loading="lazy"
                                class="h-14 w-auto max-w-full object-contain opacity-70 grayscale transition duration-300 group-hover:opacity-100 group-hover:grayscale-0"
                            >
                            <span class="text-center text-xs font-medium text-slate-500 transition group-hover:text-navy-900">
                                {{ $portfolio->client_name ?: $portfolio->t('title') }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
