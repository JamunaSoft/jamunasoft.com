@extends('layouts.app')

@section('content')
    <section class="bg-slate-50 py-24">
        <div class="mx-auto max-w-xl px-4 text-center sm:px-6">
            <p class="text-7xl font-bold text-gradient">404</p>
            <h1 class="mt-4 text-3xl font-bold text-navy-900">{{ __('Page not found') }}</h1>
            <p class="mt-4 text-slate-600">{{ __('The page you are looking for may have been moved, renamed or never existed.') }}</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                <x-button :href="route('home')" variant="primary">{{ __('Back to Home') }}</x-button>
                <x-button :href="route('services.index')" variant="outline">{{ __('Our Services') }}</x-button>
                <x-button :href="route('contact.form')" variant="outline">{{ __('Contact Us') }}</x-button>
            </div>
        </div>
    </section>
@endsection
