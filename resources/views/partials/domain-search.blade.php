{{-- Domain search band: submits to the domain availability page. --}}
<section class="border-t border-white/10 bg-navy-900 py-16" aria-label="{{ __('Domain search') }}">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-accent-400">{{ __('Domains') }}</p>
        <h2 class="mt-2 text-3xl font-bold text-white sm:text-4xl">{{ __('Find Your Perfect Domain') }}</h2>
        <p class="mt-3 text-slate-300">{{ __('Register or transfer your domain with free DNS and local support.') }}</p>

        <form action="{{ route('domains.index') }}" method="GET" class="mx-auto mt-8 flex max-w-2xl flex-col gap-3 sm:flex-row">
            <label for="home-domain-search" class="sr-only">{{ __('Domain name') }}</label>
            <input
                id="home-domain-search"
                type="text"
                name="q"
                required
                placeholder="{{ __('yourbusiness.com') }}"
                class="w-full rounded-full border-0 bg-white px-6 py-4 text-base text-navy-900 placeholder:text-slate-400 focus:ring-2 focus:ring-accent-500"
            >
            <button
                type="submit"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500 px-8 py-4 text-base font-semibold text-white shadow-sm transition-colors hover:from-brand-700 hover:to-accent-600"
            >
                {{ __('Search Domain') }}
            </button>
        </form>

        @if ($searchTlds->isNotEmpty())
            <div class="mt-6 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-slate-300">
                @foreach ($searchTlds as $tld)
                    <span><span class="font-semibold text-white">.{{ $tld->tld }}</span> ৳{{ number_format((float) $tld->register_price) }}/yr</span>
                @endforeach
            </div>
        @endif
    </div>
</section>
