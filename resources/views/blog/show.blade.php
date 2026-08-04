@extends('layouts.app')

@section('content')
    @php $featuredImage = $post->getFirstMediaUrl('featured', 'card'); @endphp

    <article>
        <header class="relative overflow-hidden bg-navy-950">
            <div class="absolute inset-0 opacity-30" aria-hidden="true">
                <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-brand-600 blur-3xl"></div>
            </div>
            <div class="relative mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <x-breadcrumbs
                    :items="[['label' => __('Blog'), 'url' => route('blog.index')], ['label' => $post->t('title')]]"
                    class="mb-5 [&_a]:text-slate-400 [&_a:hover]:text-white [&_span]:text-slate-200"
                />
                <div class="flex flex-wrap items-center gap-2">
                    @if ($post->category)
                        <a href="{{ route('blog.category', $post->category) }}"><x-badge color="brand">{{ $post->category->t('name') }}</x-badge></a>
                    @endif
                </div>
                <h1 class="mt-4 text-3xl font-bold tracking-tight text-white md:text-4xl">{{ $post->t('title') }}</h1>
                <p class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-400">
                    @if ($post->author)
                        <span>{{ __('By :name', ['name' => $post->author->name]) }}</span>
                        <span aria-hidden="true">·</span>
                    @endif
                    <time datetime="{{ $post->published_at?->toDateString() }}">{{ $post->published_at?->translatedFormat('j F Y') }}</time>
                    @if ($post->reading_time)
                        <span aria-hidden="true">·</span>
                        <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                    @endif
                </p>
            </div>
        </header>

        <div class="bg-white py-12 lg:py-16">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                @if ($featuredImage)
                    <img src="{{ $featuredImage }}" alt="{{ $post->t('title') }}" class="mb-10 w-full rounded-2xl shadow-sm" />
                @endif

                <div class="rich-text">
                    {!! $post->t('content') !!}
                </div>

                @if ($post->tags->isNotEmpty())
                    <div class="mt-10 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-8">
                        <span class="text-sm font-semibold text-navy-900">{{ __('Tags:') }}</span>
                        @foreach ($post->tags as $tag)
                            <a href="{{ route('blog.tag', $tag) }}"><x-badge>#{{ $tag->name }}</x-badge></a>
                        @endforeach
                    </div>
                @endif

                {{-- Share links --}}
                @php $shareUrl = urlencode(route('blog.show', $post)); $shareTitle = urlencode($post->t('title')); @endphp
                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <span class="text-sm font-semibold text-navy-900">{{ __('Share:') }}</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:underline">Facebook</a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ $shareUrl }}&title={{ $shareTitle }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:underline">LinkedIn</a>
                    <a href="https://x.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:underline">X</a>
                    <a href="https://wa.me/?text={{ $shareTitle }}%20{{ $shareUrl }}" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 hover:underline">WhatsApp</a>
                </div>
            </div>
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="bg-slate-50 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-section-heading :eyebrow="__('Keep reading')" :title="__('Related Articles')" />
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    @foreach ($related as $relatedPost)
                        <x-blog-card :post="$relatedPost" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <x-cta-section />

    @push('jsonld')
        <script type="application/ld+json">
            {!! json_encode(array_filter([
                '@context' => 'https://schema.org',
                '@type' => 'Article',
                'headline' => $post->t('title'),
                'description' => str(strip_tags((string) ($post->t('excerpt') ?: $post->t('content'))))->limit(160)->toString(),
                'image' => $featuredImage ?: null,
                'datePublished' => $post->published_at?->toIso8601String(),
                'dateModified' => $post->updated_at?->toIso8601String(),
                'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => settings('company_name', config('app.name')),
                    'url' => url('/'),
                ],
                'mainEntityOfPage' => route('blog.show', $post),
            ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
        </script>
    @endpush
@endsection
