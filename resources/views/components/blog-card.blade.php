@props(['post'])

@php
    $image = $post->getFirstMediaUrl('featured', 'card');
@endphp

<article {{ $attributes->merge(['class' => 'group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-lg']) }}>
    @if ($image)
        <img src="{{ $image }}" alt="{{ $post->t('title') }}" loading="lazy" class="h-48 w-full object-cover" />
    @else
        <x-placeholder-image class="h-48 w-full" />
    @endif
    <div class="flex flex-1 flex-col p-6">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
            @if ($post->category)
                <x-badge>{{ $post->category->t('name') }}</x-badge>
            @endif
            @if ($post->published_at)
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('j M Y') }}</time>
            @endif
            <span aria-hidden="true">&middot;</span>
            <span>{{ __(':min min read', ['min' => max(1, (int) $post->reading_time)]) }}</span>
        </div>
        <h3 class="mt-3 text-lg font-bold text-navy-900">
            <a href="{{ route('blog.show', $post) }}" class="focus-visible:outline-none">
                <span class="absolute inset-0" aria-hidden="true"></span>
                {{ $post->t('title') }}
            </a>
        </h3>
        @if ($post->t('excerpt'))
            <p class="mt-2 text-sm leading-relaxed text-slate-600 line-clamp-3">{{ $post->t('excerpt') }}</p>
        @endif
    </div>
</article>
