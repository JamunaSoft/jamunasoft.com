@php
    $image = ! empty($data['image']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($data['image']) : null;
    $imageLeft = ($data['image_position'] ?? 'right') === 'left';
@endphp

<section class="bg-white py-14">
    <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
        <div @class(['lg:order-2' => $imageLeft])>
            @if (! empty($data['heading']))
                <h2 class="text-2xl font-bold text-navy-900 md:text-3xl">{{ $data['heading'] }}</h2>
            @endif
            <div class="rich-text mt-4">
                {!! $data['content'] ?? '' !!}
            </div>
        </div>
        <div @class(['lg:order-1' => $imageLeft])>
            @if ($image)
                <img src="{{ $image }}" alt="{{ $data['heading'] ?? '' }}" loading="lazy" class="w-full rounded-2xl shadow-sm" />
            @else
                <x-placeholder-image class="aspect-[4/3] w-full rounded-2xl" :label="$data['heading'] ?? ''" />
            @endif
        </div>
    </div>
</section>
