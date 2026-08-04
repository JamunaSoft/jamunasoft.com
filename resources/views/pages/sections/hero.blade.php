@php
    $image = ! empty($data['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($data['image']) : null;
@endphp

<section class="relative overflow-hidden bg-navy-950">
    <div class="absolute inset-0 opacity-30" aria-hidden="true">
        <div class="absolute -top-32 -right-32 h-96 w-96 rounded-full bg-brand-600 blur-3xl"></div>
    </div>
    <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
        <div>
            <h2 class="text-3xl font-bold tracking-tight text-white md:text-4xl">{{ $data['heading'] ?? '' }}</h2>
            @if (! empty($data['subheading']))
                <p class="mt-4 max-w-xl text-lg text-slate-300">{{ $data['subheading'] }}</p>
            @endif
            @if (! empty($data['cta_label']))
                <div class="mt-8">
                    <x-button :href="$data['cta_url'] ?? route('quote.create')" variant="primary" size="lg">{{ $data['cta_label'] }}</x-button>
                </div>
            @endif
        </div>
        @if ($image)
            <img src="{{ $image }}" alt="" class="hidden w-full rounded-2xl shadow-2xl lg:block" />
        @endif
    </div>
</section>
