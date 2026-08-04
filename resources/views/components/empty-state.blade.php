@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center']) }}>
    <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
    </svg>
    <h3 class="mt-4 text-base font-semibold text-navy-900">{{ $title ?? __('Nothing here yet') }}</h3>
    <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">{{ $description ?? __('Content is on its way. Please check back soon.') }}</p>
    @if (trim($slot))
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
