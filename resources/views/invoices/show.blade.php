@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => __('Invoice'),
        'subtitle' => $invoice->reference,
        'breadcrumbs' => [['label' => $invoice->reference]],
    ])

    @php
        $isOverdue = $invoice->isOverdue();
        $badgeClasses = match (true) {
            $isOverdue => 'bg-rose-50 text-rose-700 ring-rose-600/20',
            $invoice->status === \App\Enums\InvoiceStatus::Paid => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            $invoice->status === \App\Enums\InvoiceStatus::Unpaid => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            default => 'bg-slate-100 text-slate-600 ring-slate-500/20',
        };
        $badgeLabel = $isOverdue ? __('Overdue') : $invoice->status->getLabel();
        $balance = $invoice->balance();

        $banks = collect(is_array(settings('invoice_banks')) ? settings('invoice_banks') : [])
            ->map(fn ($bank) => array_filter([
                'account_name' => $bank['account_name'] ?? null,
                'account_number' => $bank['account_number'] ?? null,
                'bank_name' => $bank['bank_name'] ?? null,
                'branch' => $bank['branch'] ?? null,
                'routing_number' => $bank['routing_number'] ?? null,
            ]))
            ->filter(fn ($bank) => $bank !== [])
            ->values();

        $mobileLines = array_filter([
            settings('invoice_bkash') ? 'bKash: '.settings('invoice_bkash') : null,
            settings('invoice_nagad') ? 'Nagad: '.settings('invoice_nagad') : null,
            settings('invoice_rocket') ? 'Rocket: '.settings('invoice_rocket') : null,
        ]);
    @endphp

    <div class="bg-slate-50 py-16 lg:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-7 lg:p-9">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-navy-900">
                            {{ __('Invoiced to') }} {{ $invoice->user->company_name ?: $invoice->user->name }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Issued :issued', ['issued' => $invoice->created_at->format('d M Y')]) }}
                            @if ($invoice->due_at)
                                &middot; {{ __('Due :due', ['due' => $invoice->due_at->format('d M Y')]) }}
                            @endif
                        </p>
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
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="py-3 pr-4 text-navy-900">
                                        {{ $item->displayTitle() }}
                                        @if ($item->displayDescription())
                                            <p class="mt-1 text-xs leading-tight text-slate-500 whitespace-pre-line">{{ $item->displayDescription() }}</p>
                                        @endif
                                    </td>
                                    <td class="py-3 pr-4 text-right text-slate-600">{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
                                    <td class="py-3 text-right text-slate-600">৳{{ number_format((float) $item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-slate-200">
                                <td colspan="2" class="py-2.5 pr-4 text-right text-sm text-slate-500">{{ __('Sub Total') }}</td>
                                <td class="py-2.5 text-right text-slate-600">৳{{ number_format((float) $invoice->subtotal, 2) }}</td>
                            </tr>
                            @if ((float) $invoice->discount > 0)
                                <tr>
                                    <td colspan="2" class="py-2.5 pr-4 text-right text-sm text-slate-500">{{ __('Discount') }}</td>
                                    <td class="py-2.5 text-right text-slate-600">−৳{{ number_format((float) $invoice->discount, 2) }}</td>
                                </tr>
                            @endif
                            <tr class="border-t border-slate-200">
                                <td colspan="2" class="py-3 pr-4 text-right text-sm font-semibold text-navy-900">{{ __('Total') }}</td>
                                <td class="py-3 text-right text-lg font-bold text-navy-900">৳{{ number_format((float) $invoice->total, 2) }}</td>
                            </tr>
                            @if ((float) $invoice->amount_paid > 0)
                                <tr>
                                    <td colspan="2" class="py-2.5 pr-4 text-right text-sm text-slate-500">{{ __('Paid') }}</td>
                                    <td class="py-2.5 text-right text-slate-600">৳{{ number_format((float) $invoice->amount_paid, 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td colspan="2" class="py-3 pr-4 text-right text-sm font-semibold {{ $balance > 0 ? 'text-rose-600' : 'text-emerald-700' }}">{{ __('Balance Due') }}</td>
                                <td class="py-3 text-right text-lg font-bold {{ $balance > 0 ? 'text-rose-600' : 'text-emerald-700' }}">৳{{ number_format($balance, 2) }}</td>
                            </tr>
                            @if (($previousDue = $invoice->previousDueAmount()) > 0)
                                <tr>
                                    <td colspan="2" class="py-2.5 pr-4 text-right text-sm text-slate-500">{{ __('Previous due (earlier invoices)') }}</td>
                                    <td class="py-2.5 text-right text-rose-600">৳{{ number_format($previousDue, 2) }}</td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="py-3 pr-4 text-right text-sm font-semibold text-navy-900">{{ __('Total payable') }}</td>
                                    <td class="py-3 text-right text-lg font-bold text-rose-600">৳{{ number_format($balance + $previousDue, 2) }}</td>
                                </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>

                @if ($invoice->payments->isNotEmpty())
                    <div class="mt-6">
                        <h3 class="text-xs uppercase tracking-wide text-slate-500">{{ __('Transactions') }}</h3>
                        <div class="mt-2 overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($invoice->payments as $payment)
                                        <tr>
                                            <td class="py-2.5 pr-4 text-slate-600">{{ $payment->paid_at?->format('d M Y') ?? '—' }}</td>
                                            <td class="py-2.5 pr-4 text-slate-600">{{ ucfirst((string) $payment->method) ?: '—' }}</td>
                                            <td class="py-2.5 pr-4 text-slate-500">{{ $payment->transaction_id ?? '—' }}</td>
                                            <td class="py-2.5 text-right text-slate-600">৳{{ number_format((float) $payment->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if ($invoice->status === \App\Enums\InvoiceStatus::Paid)
                    <x-alert type="success" class="mt-8">
                        {{ __('This invoice was paid in full on :date. Thank you!', ['date' => $invoice->paid_at?->format('d M Y')]) }}
                    </x-alert>
                @elseif ($invoice->status === \App\Enums\InvoiceStatus::Unpaid)
                    <div class="mt-8 rounded-xl bg-slate-50 p-5 text-sm leading-relaxed text-slate-600">
                        <h3 class="text-sm font-semibold text-navy-900">{{ __('How to pay') }}</h3>
                        @if (settings('domain_payment_instructions'))
                            <p class="mt-2 whitespace-pre-line">{{ settings('domain_payment_instructions') }}</p>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-8">
                            @foreach ($banks as $bank)
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Bank Details') }}</p>
                                    @if (isset($bank['account_name']))<p class="mt-1 font-semibold text-navy-900">{{ $bank['account_name'] }}</p>@endif
                                    @if (isset($bank['account_number']))<p>A/C: {{ $bank['account_number'] }}</p>@endif
                                    @if (isset($bank['bank_name']))<p>{{ $bank['bank_name'] }}</p>@endif
                                    @if (isset($bank['branch']))<p>{{ $bank['branch'] }}</p>@endif
                                    @if (isset($bank['routing_number']))<p>{{ __('Routing') }}: {{ $bank['routing_number'] }}</p>@endif
                                </div>
                            @endforeach
                            @if ($mobileLines !== [])
                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Mobile Banking') }}</p>
                                    @foreach ($mobileLines as $line)
                                        <p class="mt-1">{{ $line }}</p>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <p class="mt-4 text-xs text-slate-500">{{ __('Please mention the invoice number :reference with the payment.', ['reference' => $invoice->reference]) }}</p>
                    </div>
                @endif

                <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-100 pt-6">
                    <x-button :href="route('invoice.pdf.public', ['reference' => $invoice->reference, 'token' => $invoice->token])" variant="outline" size="sm">
                        {{ __('Download PDF') }}
                    </x-button>
                    <x-button :href="route('contact.form')" variant="outline" size="sm">{{ __('Ask a Question') }}</x-button>
                </div>
            </div>
        </div>
    </div>
@endsection
