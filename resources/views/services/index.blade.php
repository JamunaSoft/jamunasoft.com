@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Our Services'),
        'subtitle' => __('End-to-end digital services — development, hosting, marketing and automation under one roof.'),
        'breadcrumbs' => [['label' => __('Services')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl space-y-16 px-4 sm:px-6 lg:px-8">
            @if ($categories->count() > 1)
                <nav aria-label="{{ __('Service categories') }}" class="flex flex-wrap gap-2">
                    @foreach ($categories as $category)
                        <a href="#category-{{ $category->id }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-navy-900 transition-colors hover:border-brand-600 hover:text-brand-700">{{ $category->t('name') }}</a>
                    @endforeach
                </nav>
            @endif
            @forelse ($categories as $category)
                <section aria-labelledby="category-{{ $category->id }}">
                    <h2 id="category-{{ $category->id }}" class="scroll-mt-28 text-2xl font-bold text-navy-900">{{ $category->t('name') }}</h2>
                    @if ($category->t('description'))
                        <p class="mt-2 max-w-2xl text-slate-600">{{ $category->t('description') }}</p>
                    @endif
                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($category->services as $service)
                            <x-service-card :service="$service" />
                        @endforeach
                    </div>
                </section>
            @empty
                @if ($uncategorized->isEmpty())
                    <x-empty-state :title="__('Services coming soon')" :description="__('We are preparing our service catalogue. Contact us to discuss what you need.')">
                        <x-button :href="route('contact.form')" variant="primary" size="sm">{{ __('Contact') }}</x-button>
                    </x-empty-state>
                @endif
            @endforelse

            @if ($uncategorized->isNotEmpty())
                <section aria-label="{{ __('More services') }}">
                    @if ($categories->isNotEmpty())
                        <h2 class="scroll-mt-28 text-2xl font-bold text-navy-900">{{ __('More services') }}</h2>
                    @endif
                    <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($uncategorized as $service)
                            <x-service-card :service="$service" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    <x-cta-section />
@endsection
