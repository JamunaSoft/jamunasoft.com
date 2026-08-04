<section class="bg-slate-50 py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if (! empty($data['heading']))
            <x-section-heading :title="$data['heading']" :subtitle="$data['subheading'] ?? null" centered />
        @endif
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (($data['features'] ?? []) as $feature)
                @if (is_array($feature))
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-accent-500 text-white" aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        </span>
                        <h3 class="mt-4 text-base font-bold text-navy-900">{{ $feature['title'] ?? '' }}</h3>
                        @if (! empty($feature['description']))
                            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['description'] }}</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</section>
