@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Our Portfolio'),
        'subtitle' => __('Case studies of websites, software and digital projects we have delivered.'),
        'breadcrumbs' => [['label' => __('Portfolio')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($categories->isNotEmpty())
                <nav aria-label="{{ __('Filter by category') }}" class="mb-10 flex flex-wrap gap-2">
                    <a href="{{ route('portfolio.index') }}"
                       @class(['rounded-full px-4 py-2 text-sm font-semibold transition-colors', 'bg-navy-900 text-white' => ! $activeCategory, 'bg-white text-slate-600 border border-slate-200 hover:border-brand-400 hover:text-brand-700' => $activeCategory])
                       @if (! $activeCategory) aria-current="page" @endif>
                        {{ __('All') }}
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ route('portfolio.index', ['category' => $category->slug]) }}"
                           @class(['rounded-full px-4 py-2 text-sm font-semibold transition-colors', 'bg-navy-900 text-white' => $activeCategory?->id === $category->id, 'bg-white text-slate-600 border border-slate-200 hover:border-brand-400 hover:text-brand-700' => $activeCategory?->id !== $category->id])
                           @if ($activeCategory?->id === $category->id) aria-current="page" @endif>
                            {{ $category->t('name') }}
                        </a>
                    @endforeach
                </nav>
            @endif

            @if ($portfolios->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($portfolios as $portfolio)
                        <x-portfolio-card :portfolio="$portfolio" />
                    @endforeach
                </div>
                <div class="mt-12">
                    {{ $portfolios->links() }}
                </div>
            @else
                <x-empty-state :title="__('No projects found')" :description="__('There are no published projects in this category yet.')">
                    <x-button :href="route('portfolio.index')" variant="outline" size="sm">{{ __('View all projects') }}</x-button>
                </x-empty-state>
            @endif
        </div>
    </div>

    <x-cta-section />
@endsection
