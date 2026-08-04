@php
    $companyName = settings_t('company_name', 'Jamuna Soft');
    $logoDarkPath = settings('logo_dark_path');
    $logoDarkUrl = $logoDarkPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoDarkPath) : null;
    $footerText = settings_t('footer_text');
    $copyright = settings_t('copyright_text', '© '.date('Y').' '.$companyName.'. '.__('All rights reserved.'));

    $socialIcons = [
        'facebook' => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
        'linkedin' => 'M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z',
        'youtube' => 'M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
        'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z',
        'twitter' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        'x' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
        'github' => 'M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12',
        'whatsapp' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z',
    ];
@endphp

<footer class="bg-navy-950 text-slate-300">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-10 md:grid-cols-2 lg:grid-cols-4">
            {{-- About + contact --}}
            <div>
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2" aria-label="{{ $companyName }} — {{ __('Home') }}">
                    @if ($logoDarkUrl)
                        <img src="{{ $logoDarkUrl }}" alt="{{ $companyName }}" class="h-9 w-auto" />
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white" aria-hidden="true">JS</span>
                        <span class="text-lg font-bold tracking-tight text-white">{{ $companyName }}</span>
                    @endif
                </a>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">
                    {{ $footerText ?: __('We build websites, software and digital growth for businesses in Bangladesh and beyond.') }}
                </p>
                <ul class="mt-5 space-y-2.5 text-sm">
                    @if (settings('phone_primary'))
                        <li>
                            <a href="tel:{{ preg_replace('/[^\d+]/', '', settings('phone_primary')) }}" class="inline-flex items-center gap-2 hover:text-white">
                                <svg class="h-4 w-4 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                                {{ settings('phone_primary') }}
                            </a>
                        </li>
                    @endif
                    @if (settings('email_primary'))
                        <li>
                            <a href="mailto:{{ settings('email_primary') }}" class="inline-flex items-center gap-2 hover:text-white">
                                <svg class="h-4 w-4 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                                {{ settings('email_primary') }}
                            </a>
                        </li>
                    @endif
                    @if (settings_t('office_address'))
                        <li class="flex items-start gap-2 text-slate-400">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                            <span>{{ settings_t('office_address') }}</span>
                        </li>
                    @endif
                    @if (settings_t('business_hours'))
                        <li class="flex items-start gap-2 text-slate-400">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ settings_t('business_hours') }}</span>
                        </li>
                    @endif
                </ul>
            </div>

            {{-- Services --}}
            <div>
                <h2 class="text-sm font-bold uppercase tracking-widest text-white">{{ __('Services') }}</h2>
                @if ($serviceLinks)
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($serviceLinks as $link)
                            <li><a href="{{ $link['url'] }}" class="text-slate-400 hover:text-white">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm text-slate-500">{{ __('Coming soon.') }}</p>
                @endif
            </div>

            {{-- Company --}}
            <div>
                <h2 class="text-sm font-bold uppercase tracking-widest text-white">{{ __('Company') }}</h2>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @foreach ($companyLinks as $link)
                        <li><a href="{{ $link['url'] }}" class="text-slate-400 hover:text-white">{{ $link['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            {{-- Legal + newsletter --}}
            <div>
                @if ($legalLinks)
                    <h2 class="text-sm font-bold uppercase tracking-widest text-white">{{ __('Legal') }}</h2>
                    <ul class="mt-4 space-y-2.5 text-sm">
                        @foreach ($legalLinks as $link)
                            <li><a href="{{ $link['url'] }}" class="text-slate-400 hover:text-white">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                @endif

                <div id="newsletter" class="{{ $legalLinks ? 'mt-8' : '' }}">
                    <h2 class="text-sm font-bold uppercase tracking-widest text-white">{{ __('Newsletter') }}</h2>
                    <p class="mt-3 text-sm text-slate-400">{{ __('Get tips and updates in your inbox. No spam.') }}</p>

                    @if (session('newsletter_status') === 'pending')
                        <x-alert type="success" class="mt-4 border-emerald-500/30 bg-emerald-500/10 text-emerald-300">
                            {{ __('Almost done! Please check your inbox to confirm your subscription.') }}
                        </x-alert>
                    @elseif (session('newsletter_status') === 'already')
                        <x-alert type="info" class="mt-4 border-brand-500/30 bg-brand-500/10 text-brand-300">
                            {{ __('You are already subscribed. Thank you!') }}
                        </x-alert>
                    @else
                        <form action="{{ route('newsletter.subscribe') }}" method="POST" class="mt-4">
                            @csrf
                            <label for="newsletter-email" class="sr-only">{{ __('Email') }}</label>
                            <div class="flex gap-2">
                                <input
                                    type="email"
                                    id="newsletter-email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    placeholder="{{ __('you@example.com') }}"
                                    class="w-full min-w-0 rounded-xl border border-white/15 bg-white/5 px-4 py-2.5 text-sm text-white placeholder:text-slate-500 focus:border-accent-400 focus:outline-none focus:ring-2 focus:ring-accent-400/40"
                                />
                                <button type="submit" class="shrink-0 rounded-xl bg-gradient-to-r from-brand-600 to-accent-500 px-4 py-2.5 text-sm font-semibold text-white hover:from-brand-700 hover:to-accent-600">
                                    {{ __('Subscribe') }}
                                </button>
                            </div>
                            {{-- $errors is unavailable when the 404 page renders outside the web middleware stack --}}
                            @if (($errors ?? null)?->has('email'))
                                <p class="mt-2 text-xs font-medium text-red-400">{{ $errors->first('email') }}</p>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-8 sm:flex-row">
            <p class="text-sm text-slate-500">{{ $copyright }}</p>
            @if (count($socialLinks) > 0)
                <ul class="flex items-center gap-4" aria-label="{{ __('Social media') }}">
                    @foreach ($socialLinks as $social)
                        <li>
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="text-slate-500 hover:text-white" aria-label="{{ $social['label'] ?: $social['platform'] }}">
                                @if (isset($socialIcons[strtolower((string) $social['platform'])]))
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $socialIcons[strtolower((string) $social['platform'])] }}"/></svg>
                                @else
                                    <span class="text-sm font-semibold">{{ $social['label'] ?: $social['platform'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</footer>
