@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => $page->t('title'),
        'breadcrumbs' => [['label' => $page->t('title')]],
    ])

    @if ($page->t('content'))
        <div class="bg-white py-16">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="rich-text">
                    {!! $page->t('content') !!}
                </div>
            </div>
        </div>
    @endif

    @if ($useSections)
        @foreach ($sections as $section)
            @includeIf('pages.sections.'.$section['type'], ['data' => $section['data'], 'items' => $section['items'] ?? null])
        @endforeach
    @endif

    @if ($page->template === \App\Enums\PageTemplate::Landing)
        <x-cta-section />
    @endif
@endsection
