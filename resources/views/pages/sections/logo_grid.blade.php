<section class="bg-white py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if (! empty($data['heading']))
            <x-section-heading :title="$data['heading']" centered />
        @endif
        <div class="flex flex-wrap items-center justify-center gap-10">
            @foreach (($data['logos'] ?? []) as $logo)
                @if (is_string($logo))
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($logo) }}" alt="" loading="lazy" class="max-h-12 opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0" />
                @endif
            @endforeach
        </div>
    </div>
</section>
