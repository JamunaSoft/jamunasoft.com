@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Quotation'),
        'subtitle' => $quotation->reference,
        'breadcrumbs' => [['label' => $quotation->reference]],
    ])

    @php
        $badgeClasses = match (true) {
            $quotation->isExpired() => 'bg-slate-100 text-slate-600 ring-slate-500/20',
            $quotation->status->value === 'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            $quotation->status->value === 'declined' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            default => 'bg-sky-50 text-sky-700 ring-sky-600/20',
        };
        $badgeLabel = $quotation->isExpired() ? __('Expired') : $quotation->status->getLabel();
    @endphp

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-900">{{ __('Prepared for') }} {{ $quotation->customer_name }}</h2>
                        @if ($quotation->valid_until)
                            <p class="mt-1 text-sm text-slate-500">{{ __('Valid until :date', ['date' => $quotation->valid_until->format('d M Y')]) }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset {{ $badgeClasses }}">
                        {{ $badgeLabel }}
                    </span>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <th class="py-2.5 pr-4">{{ __('Item') }}</th>
                                <th class="py-2.5 pr-4 text-right">{{ __('Qty') }}</th>
                                <th class="py-2.5 text-right">{{ __('Amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($quotation->items as $item)
                                <tr>
                                    <td class="py-3 pr-4 text-navy-900">{{ $item->description }}</td>
                                    <td class="py-3 pr-4 text-right text-slate-600">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                    <td class="py-3 text-right text-slate-600">৳{{ number_format((float) $item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            @if ((float) $quotation->discount > 0)
                                <tr class="border-t border-slate-200">
                                    <td colspan="2" class="py-2.5 pr-4 text-right text-sm text-slate-500">{{ __('Discount') }}</td>
                                    <td class="py-2.5 text-right text-slate-600">−৳{{ number_format((float) $quotation->discount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="border-t border-slate-200">
                                <td colspan="2" class="py-3 pr-4 text-right text-sm font-semibold text-navy-900">{{ __('Total') }}</td>
                                <td class="py-3 text-right text-lg font-bold text-navy-900">৳{{ number_format((float) $quotation->total, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if ($quotation->notes)
                    <div class="mt-6 rounded-xl bg-slate-50 p-5 text-sm leading-relaxed text-slate-600 whitespace-pre-line">{{ $quotation->notes }}</div>
                @endif

                @if ($quotation->status === \App\Enums\QuotationStatus::Sent && ! $quotation->isExpired())
                    <form method="POST" action="{{ route('quotation.respond', ['reference' => $quotation->reference, 'token' => $quotation->token]) }}" class="mt-8 flex flex-wrap gap-3">
                        @csrf
                        <button type="submit" name="decision" value="accept" class="inline-flex items-center justify-center gap-2 rounded-full bg-gradient-to-r from-brand-600 to-accent-500 px-8 py-4 text-base font-semibold text-white shadow-sm shadow-brand-600/25 transition-colors hover:from-brand-700 hover:to-accent-600">
                            {{ __('Accept Quotation') }}
                        </button>
                        <button type="submit" name="decision" value="decline" class="inline-flex items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-8 py-4 text-base font-semibold text-navy-900 transition-colors hover:border-rose-400 hover:text-rose-600" onclick="return confirm('{{ __('Decline this quotation?') }}')">
                            {{ __('Decline') }}
                        </button>
                    </form>
                @elseif ($quotation->status === \App\Enums\QuotationStatus::Accepted)
                    <x-alert type="success" class="mt-8">
                        {{ __('You accepted this quotation on :date. We will be in touch shortly with the invoice and next steps.', ['date' => $quotation->responded_at?->format('d M Y')]) }}
                    </x-alert>
                @elseif ($quotation->status === \App\Enums\QuotationStatus::Declined)
                    <x-alert type="info" class="mt-8">
                        {{ __('You declined this quotation. Changed your mind or need adjustments? Just contact us.') }}
                    </x-alert>
                @elseif ($quotation->isExpired())
                    <x-alert type="warning" class="mt-8">
                        {{ __('This quotation has expired. Contact us for an updated offer.') }}
                    </x-alert>
                @endif

                <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                    <x-button :href="route('contact.form')" variant="outline" size="sm">{{ __('Ask a Question') }}</x-button>
                </div>
            </div>
        </div>
    </div>
@endsection
