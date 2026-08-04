@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Pricing & Packages'),
        'subtitle' => __('Clear, honest pricing with no hidden costs. Every package can be customised to fit your needs.'),
        'breadcrumbs' => [['label' => __('Packages')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <nav aria-label="{{ __('Filter by category') }}" class="mb-10 flex flex-wrap justify-center gap-2">
                <a href="{{ route('packages.index') }}"
                   @class(['rounded-full px-4 py-2 text-sm font-semibold transition-colors', 'bg-navy-900 text-white' => ! $activeCategory, 'bg-white text-slate-600 border border-slate-200 hover:border-brand-400 hover:text-brand-700' => $activeCategory])
                   @if (! $activeCategory) aria-current="page" @endif>
                    {{ __('All') }}
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('packages.index', ['category' => $category->value]) }}"
                       @class(['rounded-full px-4 py-2 text-sm font-semibold transition-colors', 'bg-navy-900 text-white' => $activeCategory === $category, 'bg-white text-slate-600 border border-slate-200 hover:border-brand-400 hover:text-brand-700' => $activeCategory !== $category])
                       @if ($activeCategory === $category) aria-current="page" @endif>
                        {{ $category->getLabel() }}
                    </a>
                @endforeach
            </nav>

            @if ($packages->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($packages as $package)
                        <x-package-card :package="$package" />
                    @endforeach
                </div>
            @else
                <x-empty-state :title="__('No packages in this category yet')" :description="__('Request a custom quotation and we will prepare pricing for your exact requirements.')">
                    <x-button :href="route('quote.create')" variant="primary" size="sm">{{ __('Request a Quotation') }}</x-button>
                </x-empty-state>
            @endif

            <p class="mx-auto mt-12 max-w-2xl text-center text-sm text-slate-500">
                {{ __('Need something that doesn\'t fit a package? Every project can be scoped individually — request a quotation and we will prepare a custom proposal.') }}
            </p>
        </div>
    </div>

    <x-cta-section />
@endsection
