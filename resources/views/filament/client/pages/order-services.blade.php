<x-filament-panels::page>
    @php
        $hostingPlans = $this->hostingPlans;
        $packages = $this->packages;

        $cardStyle = 'display:flex;flex-direction:column;gap:0.5rem;border:1px solid rgba(128,128,128,0.25);border-radius:0.75rem;padding:1.1rem 1.2rem;height:100%;';
        $gridStyle = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;';
    @endphp

    @if ($hostingPlans->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Hosting Plans</x-slot>
            <x-slot name="description">Fast BDIX hosting managed by our team. Request a plan and we will confirm with an invoice.</x-slot>

            <div style="{{ $gridStyle }}">
                @foreach ($hostingPlans as $plan)
                    <div style="{{ $cardStyle }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                            <span style="font-weight:700;">{{ $plan->name }}</span>
                            @if ($plan->is_recommended)
                                <x-filament::badge color="success">Popular</x-filament::badge>
                            @endif
                        </div>
                        <div style="font-size:1.15rem;font-weight:700;">
                            @if ($plan->yearly_price)
                                ৳{{ number_format((float) ($plan->discounted_price ?: $plan->yearly_price)) }}<span style="font-size:0.8rem;font-weight:400;opacity:0.7;">/year</span>
                            @elseif ($plan->monthly_price)
                                ৳{{ number_format((float) $plan->monthly_price) }}<span style="font-size:0.8rem;font-weight:400;opacity:0.7;">/month</span>
                            @endif
                        </div>
                        <ul style="margin:0;padding:0;list-style:none;font-size:0.85rem;opacity:0.8;display:flex;flex-direction:column;gap:0.15rem;">
                            @foreach (collect([$plan->storage ? $plan->storage.' storage' : null, $plan->bandwidth ? $plan->bandwidth.' bandwidth' : null])->merge(collect($plan->features ?? [])->take(3))->filter()->take(4) as $feature)
                                <li>&check; {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <div style="margin-top:auto;padding-top:0.5rem;">
                            <x-filament::button
                                size="sm"
                                wire:click="mountAction('requestService', { type: 'hosting', id: {{ $plan->id }} })"
                            >
                                Request this plan
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if ($packages->isNotEmpty())
        <x-filament::section>
            <x-slot name="heading">Website &amp; Software Packages</x-slot>
            <x-slot name="description">Design, development and digital marketing packages.</x-slot>

            <div style="{{ $gridStyle }}">
                @foreach ($packages as $package)
                    <div style="{{ $cardStyle }}">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">
                            <span style="font-weight:700;">{{ $package->name }}</span>
                            @if ($package->is_recommended)
                                <x-filament::badge color="success">Popular</x-filament::badge>
                            @endif
                        </div>
                        <div style="font-size:1.15rem;font-weight:700;">
                            @if ($package->is_starting_from)<span style="font-size:0.8rem;font-weight:400;opacity:0.7;">From </span>@endif
                            ৳{{ number_format((float) ($package->discounted_price ?: $package->price)) }}
                            @if ($package->price_suffix)<span style="font-size:0.8rem;font-weight:400;opacity:0.7;">{{ $package->price_suffix }}</span>@endif
                        </div>
                        @if ($package->excerpt)
                            <p style="margin:0;font-size:0.85rem;opacity:0.8;">{{ str($package->excerpt)->limit(90) }}</p>
                        @endif
                        <ul style="margin:0;padding:0;list-style:none;font-size:0.85rem;opacity:0.8;display:flex;flex-direction:column;gap:0.15rem;">
                            @foreach (collect($package->features ?? [])->take(4) as $feature)
                                <li>&check; {{ $feature }}</li>
                            @endforeach
                        </ul>
                        <div style="margin-top:auto;padding-top:0.5rem;">
                            <x-filament::button
                                size="sm"
                                wire:click="mountAction('requestService', { type: 'package', id: {{ $package->id }} })"
                            >
                                Request this package
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    @endif

    @if ($hostingPlans->isEmpty() && $packages->isEmpty())
        <x-filament::section>
            No services are available to order right now. Please <a href="{{ route('contact.form') }}" style="text-decoration:underline;">contact us</a>.
        </x-filament::section>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
