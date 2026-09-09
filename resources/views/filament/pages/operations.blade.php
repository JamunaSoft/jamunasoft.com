<x-filament-panels::page>
    <style>
        .ops-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:16px}
        .ops-card{display:block;padding:24px;border:1px solid #94a3b830;border-radius:14px;transition:border-color .2s}
        .ops-card:hover{border-color:#3b82f6}.ops-count{font-size:34px;font-weight:700;line-height:1.3;margin:12px 0}
        .ops-hint{font-size:13px;opacity:.7;line-height:1.6}.ops-row{padding:18px 0;border-bottom:1px solid #94a3b825;display:flex;gap:20px;justify-content:space-between;align-items:flex-start}
        .ops-row:last-child{border-bottom:0}.ops-error{font-size:13px;line-height:1.6;margin-top:8px;overflow-wrap:anywhere;max-width:850px;color:light-dark(#b91c1c,#fca5a5)}
    </style>
    <p class="ops-hint">Your daily overview. Review the items below, then open the relevant workspace to take action.</p>
    <div class="ops-grid">
        @foreach ($cards as $card)
            <a class="ops-card" href="{{ url($card['url']) }}">
                <h2>{{ $card['label'] }}</h2><p class="ops-count">{{ number_format($card['count']) }}</p><p class="ops-hint">{{ $card['hint'] }} →</p>
            </a>
        @endforeach
    </div>
    @if (auth()->user()->canAny(['domains.view', 'domains.manage']))
        <x-filament::section heading="Domain orders to watch" description="Processing transfers can take several days. An accepted request is not a completed transfer.">
            @forelse ($orders as $order)
                <div class="ops-row">
                    <div><strong>{{ $order->domain_name }}</strong><p class="ops-hint">{{ $order->reference }} · {{ $order->updated_at->diffForHumans() }}</p>
                        @if ($order->error_message)<p class="ops-error">{{ $order->error_message }}</p>@endif
                    </div>
                    <x-filament::badge :color="$order->status->getColor()">{{ $order->status->getLabel() }}</x-filament::badge>
                </div>
            @empty
                <p class="ops-hint">No failed or processing domain orders to review.</p>
            @endforelse
            <div style="margin-top:20px"><x-filament::link :href="url('/admin/domain-orders')">Open domain orders →</x-filament::link></div>
        </x-filament::section>
    @endif
    @if ($system !== null)
        <x-filament::section heading="Delivery & background tasks">
            <div class="ops-grid">
                <div><strong>{{ $system['failed_jobs'] }} failed jobs</strong><p class="ops-hint">{{ $system['failed_jobs'] ? 'Requires review. Resolve the cause before replaying a task.' : 'No failed jobs recorded.' }}</p></div>
                <div><strong>{{ $system['queued_jobs'] ?? 'External' }} queued tasks</strong><p class="ops-hint">Includes scheduled checks and emails; a queue is not proof of a failure.</p></div>
                <div><strong>{{ $system['mail_configured'] ? 'Mail transport configured' : 'Mail uses a test transport' }}</strong><p class="ops-hint">Delivery to the recipient still depends on the mail provider.</p></div>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
