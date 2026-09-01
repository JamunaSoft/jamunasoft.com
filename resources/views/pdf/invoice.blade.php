<?php

use App\Enums\InvoiceStatus;

$fmt = fn ($n) => number_format((float) $n, 2).' BDT';

$statusColor = match ($invoice->status) {
    InvoiceStatus::Draft => '#75808F',
    InvoiceStatus::Unpaid => '#C43D3D',
    InvoiceStatus::Paid => '#1E9E58',
    InvoiceStatus::Cancelled => '#4A5462',
    InvoiceStatus::Refunded => '#B04A20',
};

$watermark = match ($invoice->status) {
    InvoiceStatus::Draft => 'DRAFT',
    InvoiceStatus::Cancelled => 'CANCELLED',
    default => null,
};

$methodLabels = [
    'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket',
    'bank' => 'Bank Transfer', 'cash' => 'Cash', 'other' => 'Other',
];

$hasExpiry = $expiries !== [];
$balance = $invoice->balance();

// Bank accounts come from the "Bank accounts" repeater on the Billing
// settings tab; the pre-repeater single-bank keys still work as fallback.
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

if ($banks->isEmpty()) {
    $legacy = array_filter([
        'account_name' => settings('invoice_bank_account_name'),
        'account_number' => settings('invoice_bank_account_number'),
        'bank_name' => settings('invoice_bank_name'),
        'branch' => settings('invoice_bank_branch'),
    ]);

    if ($legacy !== []) {
        $banks = collect([$legacy]);
    }
}

$mobileLines = array_filter([
    settings('invoice_bkash') ? 'bKash: '.settings('invoice_bkash') : null,
    settings('invoice_nagad') ? 'Nagad: '.settings('invoice_nagad') : null,
    settings('invoice_rocket') ? 'Rocket: '.settings('invoice_rocket') : null,
]);

$client = $invoice->user;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $invoice->reference }}</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 11px; line-height: 1.4; color: #24313F;
        padding: 28px 44px 86px;
    }
    .muted { color: #5D6E7E; }
    .amount { text-align: right; white-space: nowrap; }

    .watermark {
        position: fixed; top: 300px; left: 0; width: 100%; z-index: -100;
        text-align: center; transform: rotate(-30deg);
        font-size: 95px; font-weight: bold; letter-spacing: 8px; color: #EDEFF2;
        opacity: 0.6;
    }
    .ribbon {
        position: fixed; top: 30px; right: -58px; width: 220px;
        transform: rotate(45deg);
        text-align: center; padding: 6px 0;
        background: {{ $statusColor }}; color: #ffffff;
        font-size: 13px; font-weight: bold; letter-spacing: 3px;
    }

    table.layout { width: 100%; border-collapse: collapse; }
    table.layout td { vertical-align: top; }

    .tagline { font-size: 8.5px; color: #5D6E7E; margin-top: 4px; }
    .from { text-align: right; font-size: 10.5px; color: #5D6E7E; padding-top: 10px; }
    .from .co { font-size: 12px; font-weight: bold; color: #24313F; }

    .h1 {
        font-size: 33px; font-weight: bold; letter-spacing: 3px; color: #1D3765; line-height: 1;
    }
    .h1-bar { width: 62px; height: 4px; background: #00AEEF; margin-top: 8px; }
    .meta-box { background: #F1F7FB; border-radius: 5px; padding: 8px 14px; width: 230px; }
    .meta-box table { width: 100%; border-collapse: collapse; }
    .meta-box td { padding: 1.5px 0; font-size: 10.5px; }
    .meta-box td.k { color: #5D6E7E; padding-right: 16px; white-space: nowrap; }
    .meta-box td.v { text-align: right; font-weight: bold; }
    .meta-box td.ref { text-align: right; font-weight: bold; font-size: 13px; color: #1D3765; }

    .sec-label {
        font-size: 9px; font-weight: bold; letter-spacing: 1.5px; color: #00AEEF;
        text-transform: uppercase; margin-bottom: 4px;
    }

    table.items { width: 100%; border-collapse: collapse; margin-top: 16px; }
    table.items th {
        background: #00AEEF; color: #ffffff;
        font-size: 9.5px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;
        padding: 8px 11px; text-align: left;
    }
    table.items td { padding: 7px 11px; border-bottom: 1px solid #DCE6ED; }
    table.items tr.alt td { background: #F1F7FB; }
    .item-title { font-weight: bold; }
    .item-detail { color: #5D6E7E; font-size: 10px; margin-top: 1px; }

    table.totals { width: 100%; border-collapse: collapse; }
    table.totals td { padding: 4px 11px; }
    table.totals td.k { text-align: right; color: #5D6E7E; }
    table.totals td.v { text-align: right; width: 120px; font-weight: bold; white-space: nowrap; }
    table.totals tr.grand td { border-top: 2px solid #1D3765; color: #1D3765; font-size: 12.5px; font-weight: bold; padding-top: 8px; }

    .price-note { font-size: 9.5px; color: #5D6E7E; margin-top: 6px; }
    .in-words { margin-top: 9px; font-weight: bold; }
    .nb {
        margin-top: 10px; padding: 7px 12px;
        background: #F1F7FB; border-left: 3px solid #00AEEF;
        font-size: 10px; color: #5D6E7E;
    }

    table.txns { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.txns th {
        background: #1D3765; color: #ffffff;
        font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase;
        padding: 6px 11px; text-align: left;
    }
    table.txns td { padding: 6px 11px; border-bottom: 1px solid #DCE6ED; font-size: 10.5px; }
    table.txns td.balance-label { text-align: right; font-weight: bold; color: #5D6E7E; border-bottom: none; }
    table.txns td.balance { text-align: right; font-weight: bold; border-bottom: none; white-space: nowrap; }

    .pay-col { font-size: 10.5px; padding-right: 8px; }
    .pay-col .co { font-weight: bold; }
    .pay-sm { font-size: 9px; }
    .pay-sm .sec-label { font-size: 8px; letter-spacing: 1px; }

    .thanks {
        text-align: center; margin-top: 16px;
        font-size: 12px; font-weight: bold; letter-spacing: 3px; color: #1D3765;
    }
    .generated { text-align: center; font-size: 8.5px; color: #5D6E7E; margin-top: 6px; }

    .footer {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: #00AEEF; color: #ffffff;
        padding: 9px 44px; font-size: 10px;
    }
    .footer table { width: 100%; border-collapse: collapse; }
    .footer td.r { text-align: right; }
</style>
</head>
<body>

@if ($watermark)
    <div class="watermark">{{ $watermark }}</div>
@endif

<div class="ribbon">{{ strtoupper($invoice->status->getLabel()) }}</div>

<div class="footer">
    <table>
        <tr>
            <td>{{ settings('office_address', '456/1, 3rd Floor, Monipur, Mirpur, Dhaka-1216, Bangladesh') }}</td>
            <td class="r">
                <b>Email:</b> {{ settings('email_primary', 'info@jamunasoft.com') }}
                &nbsp; <b>Cell:</b> {{ settings('phone_primary', '(+88) 01618220044') }}
            </td>
        </tr>
    </table>
</div>

{{-- header: logo left, company block right --}}
<div>
    @if ($logo)
        <img src="{{ $logo }}" style="width: 150px;" alt="{{ settings('company_name', 'Jamuna Soft') }}">
    @else
        <div class="h1" style="font-size: 22px;">{{ settings('company_name', 'Jamuna Soft') }}</div>
    @endif
    @if (settings('invoice_tagline'))
        <div class="tagline">{{ settings('invoice_tagline') }}</div>
    @endif
</div>

{{-- title + meta --}}
<table class="layout" style="margin-top: 6px;">
    <tr>
        <td style="vertical-align: top; padding-top: 10px;">
            <div class="h1">INVOICE</div>
            <div class="h1-bar"></div>
        </td>
        <td class="from" style="width: 45%;">
            <div class="co">{{ settings('company_name', 'Jamuna Soft') }}</div>
            {!! nl2br(e(settings('office_address', ''))) !!}<br>
            {{ settings('email_primary', '') }}<br>
            {{ settings('phone_primary', '') }}
        </td>
    </tr>
</table>

{{-- invoiced to --}}
<table class="layout" style="margin-top: 14px;">
    <tr>
        <td>
            <div class="sec-label">Invoiced To</div>
            @if ($client->company_name)
                <div style="font-weight: bold; font-size: 13px;">{{ $client->company_name }}</div>
                <div class="muted">ATTN: {{ $client->name }}</div>
            @else
                <div style="font-weight: bold; font-size: 13px;">{{ $client->name }}</div>
            @endif
            @if ($client->address)<div>{{ $client->address }}</div>@endif
            @php $cityLine = trim(implode(', ', array_filter([trim(($client->city ?? '').' '.($client->postal_code ?? '')), $client->country]))); @endphp
            @if ($cityLine !== '')<div>{{ $cityLine }}</div>@endif
            <div class="muted">{{ $client->email }}</div>
        </td>
        <td style="width: 230px;">
            <div class="meta-box">
                <table>
                    <tr><td class="k">Invoice No.</td><td class="ref">{{ $invoice->reference }}</td></tr>
                    <tr><td class="k">Invoice Date</td><td class="v">{{ $invoice->created_at->format('F j, Y') }}</td></tr>
                    @if ($invoice->due_at)
                        <tr><td class="k">Due Date</td><td class="v">{{ $invoice->due_at->format('F j, Y') }}</td></tr>
                    @endif
                    @if ($invoice->paid_at)
                        <tr><td class="k">Paid On</td><td class="v">{{ $invoice->paid_at->format('F j, Y') }}</td></tr>
                    @endif
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- items --}}
<table class="items">
    <thead>
        <tr>
            <th>Description</th>
            @if ($hasExpiry)<th style="text-align: center; width: 95px;">Expiry Date</th>@endif
            <th style="text-align: right; width: 110px;">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $i => $item)
            @php
                $detail = $item->displayDescription();
                $detailLines = $detail !== null ? preg_split('/\r\n|\r|\n/', $detail) : [];
            @endphp
            <tr @class(['alt' => $i % 2 === 1])>
                <td>
                    <div class="item-title">
                        {{ $item->displayTitle() }}@if ((float) $item->quantity !== 1.0) &times; {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}@endif
                    </div>
                    @foreach ($detailLines as $line)
                        @if (trim($line) !== '')<div class="item-detail">{{ $line }}</div>@endif
                    @endforeach
                </td>
                @if ($hasExpiry)
                    <td style="text-align: center;" class="muted">
                        {{ isset($expiries[$item->id]) ? $expiries[$item->id]->format('M d, Y') : '—' }}
                    </td>
                @endif
                <td class="amount" style="font-weight: bold;">{{ $fmt($item->total) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
<table class="totals">
    @if ((float) $invoice->discount > 0)
        <tr><td class="k">Sub Total</td><td class="v">{{ $fmt($invoice->subtotal) }}</td></tr>
        <tr><td class="k">Discount</td><td class="v">&minus;{{ $fmt($invoice->discount) }}</td></tr>
    @endif
    <tr class="grand"><td class="k" style="color: #1D3765;">Total</td><td class="v">{{ $fmt($invoice->total) }}</td></tr>
</table>
<div class="price-note">{{ settings('invoice_price_note', '* All prices are excluding of VAT & AIT') }}</div>

<div class="in-words"><span class="muted" style="font-weight: normal;">In words:</span> {{ taka_in_words((float) $invoice->total) }}</div>

<div class="nb">
    @if ($invoice->notes)
        {!! nl2br(e($invoice->notes)) !!}
    @else
        <b style="color: #24313F;">NB:</b>
        {{ settings('invoice_note', 'Please be aware that if your hosting expires, any website or email services associated with it will stop working. Renew it now to avoid interruption in service.') }}
    @endif
</div>

{{-- transactions --}}
<div style="margin-top: 14px;">
    <div class="sec-label">Transactions</div>
    <table class="txns">
        <thead>
            <tr>
                <th>Date</th>
                <th>Gateway</th>
                <th>Transaction ID</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at?->format('F j, Y') ?? '—' }}</td>
                    <td>{{ $methodLabels[$payment->method] ?? ucfirst((string) $payment->method) ?: '—' }}</td>
                    <td class="muted">{{ $payment->transaction_id ?? '—' }}</td>
                    <td class="amount" style="font-weight: bold;">{{ $fmt($payment->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="font-style: italic;">No transactions recorded</td></tr>
            @endforelse
            <tr>
                <td colspan="3" class="balance-label">Balance</td>
                <td class="balance" style="color: {{ $balance > 0 ? '#C43D3D' : '#1E9E58' }};">{{ $fmt($balance) }}</td>
            </tr>
        </tbody>
    </table>
</div>

{{-- payment methods: bank columns side by side, then mobile banking --}}
@php
    $payCols = $banks->count() + ($mobileLines !== [] ? 1 : 0);
    $payColWidth = $payCols > 0 ? floor(100 / $payCols) : 100;
    $paySmall = $payCols > 2; // shrink so 2–3 banks + mobile stay on one row
@endphp
@if ($payCols > 0)
    <table class="layout" style="margin-top: 14px;">
        <tr>
            @foreach ($banks as $bank)
                <td style="width: {{ $payColWidth }}%;" class="pay-col {{ $paySmall ? 'pay-sm' : '' }}">
                    <div class="sec-label">Bank Details</div>
                    @if (isset($bank['account_name']))<div class="co">{{ $bank['account_name'] }}</div>@endif
                    @if (isset($bank['account_number']))<div>A/C: {{ $bank['account_number'] }}</div>@endif
                    @if (isset($bank['bank_name']))<div>{{ $bank['bank_name'] }}</div>@endif
                    @if (isset($bank['branch']))<div>{{ $bank['branch'] }}</div>@endif
                    @if (isset($bank['routing_number']))<div>Routing: {{ $bank['routing_number'] }}</div>@endif
                </td>
            @endforeach
            @if ($mobileLines !== [])
                <td style="width: {{ $payColWidth }}%;" class="pay-col {{ $paySmall ? 'pay-sm' : '' }}">
                    <div class="sec-label">Mobile Banking</div>
                    @foreach ($mobileLines as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </td>
            @endif
        </tr>
    </table>
@endif

<div class="thanks">THANK YOU FOR YOUR BUSINESS</div>
<div class="generated">PDF generated on {{ now()->format('l, F jS, Y') }}</div>

</body>
</html>
