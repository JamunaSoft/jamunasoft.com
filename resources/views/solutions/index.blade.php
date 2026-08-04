@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Industry Solutions'),
        'subtitle' => __('Purpose-built digital solutions for the industries we know best.'),
        'breadcrumbs' => [['label' => __('Solutions')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($solutions->isNotEmpty())
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($solutions as $solution)
                        <a href="{{ route('solutions.show', $solution) }}" class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-7 shadow-sm transition-shadow hover:shadow-lg">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600" aria-hidden="true">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" /></svg>
                            </span>
                            <h2 class="mt-5 text-lg font-bold text-navy-900 group-hover:text-brand-700">{{ $solution->t('name') }}</h2>
                            @if ($solution->t('excerpt'))
                                <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">{{ $solution->t('excerpt') }}</p>
                            @endif
                            <span class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600">
                                {{ __('Explore solution') }}
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <x-empty-state :title="__('Solutions coming soon')" :description="__('We are documenting our industry solutions. Contact us to discuss your industry.')">
                    <x-button :href="route('contact.form')" variant="primary" size="sm">{{ __('Contact') }}</x-button>
                </x-empty-state>
            @endif
        </div>
    </div>

    <x-cta-section />
@endsection
