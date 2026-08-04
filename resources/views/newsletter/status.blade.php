@extends('layouts.app')

@section('content')
    @php
        $content = match ($state) {
            'confirmed' => [
                'icon' => 'success',
                'title' => __('Subscription confirmed!'),
                'description' => __('Thank you — you will now receive our occasional updates and insights. No spam, ever.'),
            ],
            'unsubscribed' => [
                'icon' => 'success',
                'title' => __('You have been unsubscribed.'),
                'description' => __('You will no longer receive emails from our newsletter. You can re-subscribe any time from the website footer.'),
            ],
            default => [
                'icon' => 'error',
                'title' => __('Invalid or expired link'),
                'description' => __('This confirmation link is not valid. Please subscribe again using the form in the footer.'),
            ],
        };
    @endphp

    <section class="bg-slate-50 py-24">
        <div class="mx-auto max-w-xl px-4 text-center sm:px-6">
            @if ($content['icon'] === 'success')
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100" aria-hidden="true">
                    <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </span>
            @else
                <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100" aria-hidden="true">
                    <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </span>
            @endif
            <h1 class="mt-6 text-3xl font-bold text-navy-900">{{ $content['title'] }}</h1>
            <p class="mt-4 text-slate-600">{{ $content['description'] }}</p>
            <div class="mt-8">
                <x-button :href="route('home')" variant="primary">{{ __('Back to Home') }}</x-button>
            </div>
        </div>
    </section>
@endsection
