@extends('layouts.app')

@section('content')
    <section class="bg-slate-50 py-24">
        <div class="mx-auto max-w-xl px-4 text-center sm:px-6">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100" aria-hidden="true">
                <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </span>
            <h1 class="mt-6 text-3xl font-bold text-navy-900">{{ __('Thank you! Your request has been received.') }}</h1>
            <p class="mt-4 text-slate-600">{{ __('Our team will review your requirements and contact you within one business day.') }}</p>

            <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
                <p class="text-sm font-semibold uppercase tracking-widest text-slate-500">{{ __('Your reference number') }}</p>
                <p class="mt-2 text-2xl font-bold tracking-wide text-brand-700">{{ $reference }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ __('Please keep this reference for future communication. A confirmation email has been sent to you.') }}</p>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <x-button :href="route('home')" variant="outline">{{ __('Back to Home') }}</x-button>
                <x-button :href="route('portfolio.index')" variant="primary">{{ __('Browse Our Work') }}</x-button>
            </div>
        </div>
    </section>
@endsection
