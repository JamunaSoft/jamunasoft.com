@props(['faqs', 'jsonld' => false])

@if ($faqs->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white']) }}>
        @foreach ($faqs as $faq)
            <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="px-6">
                <h3>
                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open"
                        aria-controls="faq-panel-{{ $faq->id }}"
                        class="flex w-full items-center justify-between gap-4 py-5 text-left text-base font-semibold text-navy-900 hover:text-brand-700"
                    >
                        <span>{{ $faq->t('question') }}</span>
                        <svg class="h-5 w-5 shrink-0 text-brand-600 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </h3>
                <div id="faq-panel-{{ $faq->id }}" x-show="open" x-cloak>
                    <p class="pb-5 text-sm leading-relaxed text-slate-600">{{ $faq->t('answer') }}</p>
                </div>
            </div>
        @endforeach
    </div>

    @if ($jsonld)
        @php
            $faqJsonLd = json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $faqs->map(fn ($faq) => [
                    '@type' => 'Question',
                    'name' => strip_tags((string) $faq->t('question')),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags((string) $faq->t('answer')),
                    ],
                ])->values()->all(),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        @endphp
        @push('jsonld')
            <script type="application/ld+json">{!! $faqJsonLd !!}</script>
        @endpush
    @endif
@endif
