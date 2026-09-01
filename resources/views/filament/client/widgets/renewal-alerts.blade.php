<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-bell-alert" icon-color="warning">
        <x-slot name="heading">Renewals due soon</x-slot>
        <x-slot name="description">If a service or domain expires, any website or email attached to it will stop working. Pay the renewal invoice to avoid interruption.</x-slot>

        <ul style="margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:0.4rem;font-size:0.9rem;">
            @foreach ($domains as $domain)
                <li>
                    <strong>{{ $domain->name }}</strong> — domain expires {{ $domain->expires_at->format('d M Y') }}
                    ({{ $domain->expires_at->diffForHumans() }})
                </li>
            @endforeach
            @foreach ($services as $service)
                <li>
                    <strong>{{ $service->name }}</strong>@if ($service->domain) ({{ $service->domain }})@endif
                    — due {{ $service->next_due_at->format('d M Y') }}
                    ({{ $service->next_due_at->diffForHumans() }})
                </li>
            @endforeach
        </ul>

        <div style="margin-top:0.9rem;">
            <x-filament::button
                tag="a"
                size="sm"
                color="warning"
                href="{{ \App\Filament\Client\Resources\InvoiceResource::getUrl() }}"
            >
                View invoices &amp; pay
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
