@props(['title', 'subtitle' => null, 'breadcrumbs' => []])

<section class="relative overflow-hidden bg-navy-950">
    <div class="absolute inset-0 opacity-30" aria-hidden="true">
        <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-brand-600 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
        <x-breadcrumbs :items="$breadcrumbs" class="mb-5 [&_a]:text-slate-400 [&_a:hover]:text-white [&_span]:text-slate-200" />
        <h1 class="text-3xl font-bold tracking-tight text-white md:text-4xl lg:text-5xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-4 max-w-2xl text-lg text-slate-300">{{ $subtitle }}</p>
        @endif
    </div>
</section>
