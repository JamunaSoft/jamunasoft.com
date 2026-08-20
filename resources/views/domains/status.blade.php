@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Domain Order'),
        'subtitle' => $order->reference,
        'breadcrumbs' => [['label' => __('Domains'), 'url' => route('domains.index')], ['label' => $order->reference]],
    ])

    @php
        $badgeClasses = match ($order->status->value) {
            'pending_payment' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            'paid', 'processing' => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            'failed' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
    @endphp

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-bold text-navy-900">{{ $order->domain_name }}</h2>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses }}">
                        {{ $order->status->getLabel() }}
                    </span>
                </div>

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Order reference') }}</dt>
                        <dd class="font-semibold text-navy-900">{{ $order->reference }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Type') }}</dt>
                        <dd class="font-semibold text-navy-900">{{ $order->type->getLabel() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Period') }}</dt>
                        <dd class="font-semibold text-navy-900">{{ $order->years }} {{ __(str('year')->plural($order->years)->toString()) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-slate-100 pt-3">
                        <dt class="text-slate-500">{{ __('Amount') }}</dt>
                        <dd class="text-base font-bold text-navy-900">৳{{ number_format((float) $order->amount, 2) }}</dd>
                    </div>
                </dl>

                @if ($order->status === \App\Enums\DomainOrderStatus::PendingPayment)
                    <div class="mt-6 rounded-xl bg-amber-50 p-5">
                        <h3 class="text-sm font-bold text-amber-800">{{ __('How to pay') }}</h3>
                        @if (settings('domain_payment_instructions'))
                            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-amber-800">{{ settings('domain_payment_instructions') }}</p>
                        @else
                            <p class="mt-2 text-sm leading-relaxed text-amber-800">{{ __('We have emailed you the payment instructions. Your domain will be activated as soon as the payment is confirmed.') }}</p>
                        @endif
                        <p class="mt-3 text-sm font-semibold text-amber-800">{{ __('Please mention your order reference :reference with the payment.', ['reference' => $order->reference]) }}</p>
                    </div>
                @elseif ($order->status === \App\Enums\DomainOrderStatus::Completed)
                    <x-alert type="success" class="mt-6">
                        {{ __('Your domain is active! We have sent you a confirmation email.') }}
                    </x-alert>
                @elseif (in_array($order->status->value, ['paid', 'processing'], true))
                    <x-alert type="info" class="mt-6">
                        {{ __('Payment received — your domain is being set up. This normally takes just a few minutes.') }}
                    </x-alert>
                @elseif ($order->status === \App\Enums\DomainOrderStatus::Failed)
                    <x-alert type="warning" class="mt-6">
                        {{ __('There was a hiccup while setting up your domain. Our team has been notified and will resolve it shortly — no further action is needed from you.') }}
                    </x-alert>
                @endif

                <div class="mt-8 flex flex-wrap gap-3">
                    <x-button :href="route('domains.index')" variant="outline" size="sm">{{ __('Search Another Domain') }}</x-button>
                    <x-button :href="route('contact.form')" variant="outline" size="sm">{{ __('Need Help?') }}</x-button>
                </div>
            </div>
        </div>
    </div>
@endsection
