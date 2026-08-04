<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    @include('partials.seo')

    @php
        $faviconPath = settings('favicon_path');
    @endphp
    <link rel="icon" href="{{ $faviconPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($faviconPath) : asset('favicon.ico') }}" />

    @include('partials.scripts-head')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-white font-sans">
    @include('partials.scripts-body-start')

    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:top-2 focus:left-2 focus:rounded-lg focus:bg-navy-900 focus:px-4 focus:py-2 focus:text-white">
        {{ __('Skip to content') }}
    </a>

    <x-site-header />

    <main id="main-content" class="flex-1">
        @yield('content')
    </main>

    <x-site-footer />

    @include('partials.whatsapp-button')

    @include('partials.jsonld-site')
    @stack('jsonld')

    @include('partials.scripts-body-end')
</body>
</html>
