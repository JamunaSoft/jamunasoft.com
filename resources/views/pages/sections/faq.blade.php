@php
    // Inline items authored on the page win; otherwise fall back to general FAQs.
    $inlineItems = collect($data['items'] ?? [])->filter(fn ($item) => is_array($item) && ! empty($item['question']));
@endphp

<section class="bg-white py-14">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        @if (! empty($data['heading']))
            <x-section-heading :title="$data['heading']" centered />
        @endif

        @if ($inlineItems->isNotEmpty())
            <div class="divide-y divide-slate-200 rounded-2xl border border-slate-200 bg-white">
                @foreach ($inlineItems as $item)
                    <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="px-6">
                        <h3>
                            <button type="button" @click="open = !open" :aria-expanded="open" class="flex w-full items-center justify-between gap-4 py-5 text-left">
                                <span class="font-semibold text-navy-900">{{ $item['question'] }}</span>
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </h3>
                        <div x-show="open" x-cloak class="pb-5 text-sm leading-relaxed text-slate-600">{{ $item['answer'] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        @elseif (($items ?? null)?->isNotEmpty())
            <x-faq-accordion :faqs="$items" />
        @endif
    </div>
</section>
