@props(['member'])

@php
    $photo = $member->getFirstMediaUrl('photo', 'card');
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm']) }}>
    @if ($photo)
        <img src="{{ $photo }}" alt="{{ $member->t('name') }}" loading="lazy" class="h-28 w-28 rounded-full object-cover" />
    @else
        <span class="flex h-28 w-28 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-3xl font-bold text-white" aria-hidden="true">
            {{ mb_substr($member->name, 0, 1) }}
        </span>
    @endif
    <h3 class="mt-4 text-base font-bold text-navy-900">{{ $member->t('name') }}</h3>
    @if ($member->designation)
        <p class="mt-1 text-sm text-brand-600">{{ $member->t('designation') }}</p>
    @endif
    @if ($member->t('bio'))
        <p class="mt-3 text-sm text-slate-600 line-clamp-3">{{ $member->t('bio') }}</p>
    @endif
    @if ($member->linkedin_url || $member->facebook_url || $member->website_url)
        <div class="mt-4 flex items-center gap-3">
            @if ($member->linkedin_url)
                <a href="{{ $member->linkedin_url }}" rel="noopener" target="_blank" class="text-slate-400 hover:text-brand-600" aria-label="{{ __('LinkedIn profile of :name', ['name' => $member->name]) }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                </a>
            @endif
            @if ($member->facebook_url)
                <a href="{{ $member->facebook_url }}" rel="noopener" target="_blank" class="text-slate-400 hover:text-brand-600" aria-label="{{ __('Facebook profile of :name', ['name' => $member->name]) }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                </a>
            @endif
            @if ($member->website_url)
                <a href="{{ $member->website_url }}" rel="noopener" target="_blank" class="text-slate-400 hover:text-brand-600" aria-label="{{ __('Website of :name', ['name' => $member->name]) }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0a8.949 8.949 0 004.951-1.488A3.987 3.987 0 0013 16.5h-2a3.987 3.987 0 00-3.951 3.012A8.949 8.949 0 0012 21zm3.75-15a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/></svg>
                </a>
            @endif
        </div>
    @endif
</article>
