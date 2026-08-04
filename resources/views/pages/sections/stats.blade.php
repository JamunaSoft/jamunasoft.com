<section class="border-y border-slate-100 bg-white">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 md:grid-cols-4 lg:px-8">
        @foreach (($data['stats'] ?? []) as $stat)
            @if (is_array($stat))
                <x-stat :value="$stat['value'] ?? ''" :label="$stat['label'] ?? ''" />
            @endif
        @endforeach
    </div>
</section>
