@php
    $logoPath = settings('logo_path');
    $logoUrl = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null;
    $companyName = settings_t('company_name', 'Jamuna Soft');
    $whatsappDigits = preg_replace('/\D+/', '', (string) settings('whatsapp_number', ''));
    $portalUrl = settings('client_portal_url');
    $ctaLabel = settings_t('header_cta_label');
    $ctaUrl = settings('header_cta_url');
    $currentLocale = app()->getLocale();
@endphp

<header
    x-data="{ open: false, scrolled: false }"
    @scroll.window.passive="scrolled = window.scrollY > 8"
    :class="scrolled ? 'shadow-md shadow-navy-900/5' : ''"
    class="sticky top-0 z-40 bg-white/95 backdrop-blur transition-shadow"
>
    <div class="mx-auto flex h-18 max-w-7xl items-center justify-between gap-6 px-4 py-3 sm:px-6 lg:px-8">
        {{-- Logo --}}
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ $companyName }} — {{ __('Home') }}">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-9 w-auto" />
            @else
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white" aria-hidden="true">JS</span>
                <span class="text-lg font-bold tracking-tight text-navy-900">{{ $companyName }}</span>
            @endif
        </a>

        {{-- Desktop nav --}}
        <nav class="hidden lg:block" aria-label="{{ __('Main navigation') }}">
            <ul class="flex items-center gap-1">
                @foreach ($items as $item)
                    <li class="relative" @if ($item['children']) x-data="{ sub: false }" @mouseenter="sub = true" @mouseleave="sub = false" @endif>
                        <a
                            href="{{ $item['url'] }}"
                            @if ($item['target']) target="{{ $item['target'] }}" @endif
                            class="inline-flex items-center gap-1 rounded-lg px-3 py-2 text-sm font-medium {{ url()->current() === url($item['url']) ? 'text-brand-700' : 'text-slate-700 hover:text-brand-700' }}"
                            @if (url()->current() === url($item['url'])) aria-current="page" @endif
                        >
                            {{ $item['label'] }}
                            @if ($item['children'])
                                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            @endif
                        </a>
                        @if ($item['children'])
                            <ul x-show="sub" x-cloak x-transition.opacity.duration.150ms class="absolute left-0 top-full z-50 mt-1 w-56 rounded-2xl border border-slate-100 bg-white p-2 shadow-lg">
                                @foreach ($item['children'] as $child)
                                    <li>
                                        <a href="{{ $child['url'] }}" @if ($child['target']) target="{{ $child['target'] }}" @endif class="block rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-700">
                                            {{ $child['label'] }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Actions --}}
        <div class="hidden items-center gap-3 lg:flex">
            {{-- Language switcher --}}
            <nav aria-label="{{ __('Language') }}" class="flex items-center gap-1 rounded-full border border-slate-200 p-1 text-xs font-semibold">
                <a href="{{ route('locale.switch', 'en') }}" class="rounded-full px-2.5 py-1 {{ $currentLocale === 'en' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:text-navy-900' }}" @if ($currentLocale === 'en') aria-current="true" @endif>EN</a>
                <a href="{{ route('locale.switch', 'bn') }}" class="rounded-full px-2.5 py-1 {{ $currentLocale === 'bn' ? 'bg-navy-900 text-white' : 'text-slate-600 hover:text-navy-900' }}" @if ($currentLocale === 'bn') aria-current="true" @endif>বাংলা</a>
            </nav>

            @if ($portalUrl)
                <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="text-sm font-semibold text-slate-600 hover:text-brand-700">{{ __('Client Portal') }}</a>
            @endif

            @if ($whatsappDigits)
                <a href="https://wa.me/{{ $whatsappDigits }}" target="_blank" rel="noopener" class="text-[#25D366] hover:opacity-80" aria-label="{{ __('Chat with us on WhatsApp') }}">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                </a>
            @endif

            <x-button :href="$ctaUrl ?: route('quote.create')" variant="primary" size="sm">
                {{ $ctaLabel ?: __('Get a Quotation') }}
            </x-button>
        </div>

        {{-- Mobile menu button --}}
        <button
            type="button"
            @click="open = true"
            class="inline-flex items-center justify-center rounded-lg p-2 text-navy-900 hover:bg-slate-100 lg:hidden"
            aria-label="{{ __('Open menu') }}"
            :aria-expanded="open"
            aria-controls="mobile-nav"
        >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>
    </div>

    {{-- Mobile slide-over --}}
    <div x-show="open" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" id="mobile-nav" @keydown.escape.window="open = false">
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-navy-950/60" @click="open = false" aria-hidden="true"></div>
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 flex w-full max-w-xs flex-col overflow-y-auto bg-white shadow-xl"
        >
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <span class="text-base font-bold text-navy-900">{{ $companyName }}</span>
                <button type="button" @click="open = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="{{ __('Close menu') }}">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="flex-1 px-3 py-4" aria-label="{{ __('Main navigation') }}">
                <ul class="space-y-1">
                    @foreach ($items as $item)
                        <li>
                            <a href="{{ $item['url'] }}" @if ($item['target']) target="{{ $item['target'] }}" @endif class="block rounded-xl px-3 py-2.5 text-base font-medium {{ url()->current() === url($item['url']) ? 'bg-brand-50 text-brand-700' : 'text-slate-700 hover:bg-slate-50' }}">
                                {{ $item['label'] }}
                            </a>
                            @if ($item['children'])
                                <ul class="ml-4 space-y-1">
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ $child['url'] }}" class="block rounded-xl px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">{{ $child['label'] }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
            <div class="space-y-4 border-t border-slate-100 px-5 py-5">
                <x-button :href="$ctaUrl ?: route('quote.create')" variant="primary" size="md" class="w-full">
                    {{ $ctaLabel ?: __('Get a Quotation') }}
                </x-button>
                @if ($portalUrl)
                    <a href="{{ $portalUrl }}" target="_blank" rel="noopener" class="block text-center text-sm font-semibold text-slate-600 hover:text-brand-700">{{ __('Client Portal') }}</a>
                @endif
                <nav aria-label="{{ __('Language') }}" class="flex items-center justify-center gap-1 rounded-full border border-slate-200 p-1 text-xs font-semibold">
                    <a href="{{ route('locale.switch', 'en') }}" class="flex-1 rounded-full px-2.5 py-1.5 text-center {{ $currentLocale === 'en' ? 'bg-navy-900 text-white' : 'text-slate-600' }}">English</a>
                    <a href="{{ route('locale.switch', 'bn') }}" class="flex-1 rounded-full px-2.5 py-1.5 text-center {{ $currentLocale === 'bn' ? 'bg-navy-900 text-white' : 'text-slate-600' }}">বাংলা</a>
                </nav>
            </div>
        </div>
    </div>
</header>
