<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-shopping-bag">
        <x-slot name="heading">Need hosting, a website or more?</x-slot>
        <x-slot name="description">Order right from your client area — we confirm with an invoice, no automatic charges.</x-slot>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:0.9rem;">
            @foreach ($products as $product)
                <div style="border:1px solid rgba(128,128,128,0.25);border-radius:0.75rem;padding:0.9rem 1rem;">
                    <div style="font-weight:600;">{{ $product['name'] }}</div>
                    <div style="margin-top:0.2rem;font-weight:700;">
                        ৳{{ number_format($product['price']) }}<span style="font-size:0.78rem;font-weight:400;opacity:0.7;">{{ $product['suffix'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div style="margin-top:0.9rem;">
            <x-filament::button
                tag="a"
                size="sm"
                href="{{ \App\Filament\Client\Pages\OrderServices::getUrl() }}"
            >
                Browse all services
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
