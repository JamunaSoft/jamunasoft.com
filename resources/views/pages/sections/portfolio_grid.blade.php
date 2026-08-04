@if (($items ?? null)?->isNotEmpty())
    <section class="bg-white py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (! empty($data['heading']))
                <x-section-heading :title="$data['heading']" centered />
            @endif
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($items as $portfolio)
                    <x-portfolio-card :portfolio="$portfolio" />
                @endforeach
            </div>
        </div>
    </section>
@endif
