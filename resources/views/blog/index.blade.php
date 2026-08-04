@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => $listTitle ?? __('Blog & Insights'),
        'subtitle' => $listDescription ?? __('Guides, tips and insights on software, websites, hosting and digital growth.'),
        'breadcrumbs' => isset($listTitle)
            ? [['label' => __('Blog'), 'url' => route('blog.index')], ['label' => $listTitle]]
            : [['label' => __('Blog')]],
    ])

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (($featured ?? null) !== null)
                <article class="mb-12 grid grid-cols-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:grid-cols-2">
                    @php $featuredImage = $featured->getFirstMediaUrl('featured', 'card'); @endphp
                    <a href="{{ route('blog.show', $featured) }}" class="block" aria-hidden="true" tabindex="-1">
                        @if ($featuredImage)
                            <img src="{{ $featuredImage }}" alt="" class="h-full w-full object-cover" />
                        @else
                            <x-placeholder-image class="h-full min-h-56 w-full" :label="__('Featured')" />
                        @endif
                    </a>
                    <div class="flex flex-col justify-center p-8 lg:p-10">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-badge color="brand">{{ __('Featured') }}</x-badge>
                            @if ($featured->category)
                                <x-badge>{{ $featured->category->t('name') }}</x-badge>
                            @endif
                        </div>
                        <h2 class="mt-4 text-2xl font-bold text-navy-900">
                            <a href="{{ route('blog.show', $featured) }}" class="hover:text-brand-700">{{ $featured->t('title') }}</a>
                        </h2>
                        @if ($featured->t('excerpt'))
                            <p class="mt-3 leading-relaxed text-slate-600 line-clamp-3">{{ $featured->t('excerpt') }}</p>
                        @endif
                        <p class="mt-5 text-sm text-slate-500">
                            {{ $featured->published_at?->translatedFormat('j F Y') }}
                            @if ($featured->reading_time) · {{ __(':min min read', ['min' => $featured->reading_time]) }} @endif
                        </p>
                    </div>
                </article>
            @endif

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-4">
                <div class="lg:col-span-3">
                    @if ($posts->isNotEmpty())
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($posts as $post)
                                <x-blog-card :post="$post" />
                            @endforeach
                        </div>
                        <div class="mt-12">
                            {{ $posts->links() }}
                        </div>
                    @else
                        <x-empty-state :title="__('No articles yet')" :description="__('We are writing our first articles. Check back soon.')" />
                    @endif
                </div>

                <aside class="space-y-8">
                    @if ($blogCategories->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-6">
                            <h2 class="text-base font-bold text-navy-900">{{ __('Categories') }}</h2>
                            <ul class="mt-4 space-y-2.5">
                                @foreach ($blogCategories as $category)
                                    <li>
                                        <a href="{{ route('blog.category', $category) }}" class="flex items-center justify-between text-sm text-slate-600 hover:text-brand-700">
                                            <span>{{ $category->t('name') }}</span>
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $category->posts_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($recentPosts->isNotEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-6">
                            <h2 class="text-base font-bold text-navy-900">{{ __('Recent posts') }}</h2>
                            <ul class="mt-4 space-y-3">
                                @foreach ($recentPosts as $recent)
                                    <li>
                                        <a href="{{ route('blog.show', $recent) }}" class="block text-sm font-medium text-slate-700 hover:text-brand-700">{{ $recent->t('title') }}</a>
                                        <span class="text-xs text-slate-400">{{ $recent->published_at?->translatedFormat('j M Y') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    <x-cta-section />
@endsection
