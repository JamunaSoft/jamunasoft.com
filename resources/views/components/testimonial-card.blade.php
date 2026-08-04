@props(['testimonial'])

@php
    $avatar = $testimonial->getFirstMediaUrl('avatar', 'thumb');
    $rating = min(5, max(0, (int) $testimonial->rating));
@endphp

<figure {{ $attributes->merge(['class' => 'flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm']) }}>
    @if ($rating > 0)
        <div class="flex gap-0.5" role="img" aria-label="{{ __(':rating out of 5 stars', ['rating' => $rating]) }}">
            @for ($i = 1; $i <= 5; $i++)
                <svg class="h-4 w-4 {{ $i <= $rating ? 'text-amber-400' : 'text-slate-200' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                </svg>
            @endfor
        </div>
    @endif
    <blockquote class="mt-4 flex-1 text-sm leading-relaxed text-slate-700">
        &ldquo;{{ $testimonial->t('quote') }}&rdquo;
    </blockquote>
    <figcaption class="mt-6 flex items-center gap-3">
        @if ($avatar)
            <img src="{{ $avatar }}" alt="" loading="lazy" class="h-11 w-11 rounded-full object-cover" />
        @else
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-brand-600 to-accent-500 text-sm font-bold text-white" aria-hidden="true">
                {{ mb_substr($testimonial->author_name, 0, 1) }}
            </span>
        @endif
        <div>
            <p class="text-sm font-bold text-navy-900">{{ $testimonial->t('author_name') }}</p>
            <p class="text-xs text-slate-500">
                {{ $testimonial->t('author_designation') }}@if ($testimonial->author_designation && $testimonial->company), @endif{{ $testimonial->t('company') }}
            </p>
        </div>
    </figcaption>
</figure>
