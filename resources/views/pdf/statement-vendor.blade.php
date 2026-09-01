<?php
$fmt = fn ($n) => number_format((float) $n, 2);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Statement — {{ $vendor->name }}</title>
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.4; color: #24313F; padding: 34px 44px 80px; }
    .muted { color: #5D6E7E; }
    .h1 { font-size: 28px; font-weight: bold; letter-spacing: 3px; color: #1D3765; }
    table.layout { width: 100%; border-collapse: collapse; }
    table.layout td { vertical-align: top; }
    table.rows { width: 100%; border-collapse: collapse; margin-top: 18px; }
    table.rows th { background: #1D3765; color: #fff; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; padding: 7px 10px; text-align: left; }
    table.rows th.r, table.rows td.r { text-align: right; white-space: nowrap; }
    table.rows td { padding: 6px 10px; border-bottom: 1px solid #DCE6ED; font-size: 10.5px; }
    table.rows tr.carry td { background: #F1F7FB; font-weight: bold; }
    table.rows tr.totals td { font-weight: bold; border-top: 2px solid #1D3765; border-bottom: none; }
    .closing { margin-top: 14px; text-align: right; font-size: 13px; font-weight: bold; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; background: #00AEEF; color: #fff; padding: 9px 44px; font-size: 10px; }
    .footer table { width: 100%; border-collapse: collapse; }
    .footer td.r { text-align: right; }
</style>
</head>
<body>

<div class="footer">
    <table><tr>
        <td>{{ settings('office_address', '') }}</td>
        <td class="r"><b>Email:</b> {{ settings('email_primary', '') }} &nbsp; <b>Cell:</b> {{ settings('phone_primary', '') }}</td>
    </tr></table>
</div>

<table class="layout">
    <tr>
        <td>
            @if ($logo)<img src="{{ $logo }}" style="width: 140px;" alt="{{ settings('company_name') }}">@endif
        </td>
        <td style="text-align: right; padding-top: 6px;">
            <div class="h1">VENDOR STATEMENT</div>
            <div class="muted" style="margin-top: 4px;">
                {{ $from ? $from->format('d M Y') : 'Beginning' }} — {{ $to->format('d M Y') }}
            </div>
        </td>
    </tr>
</table>

<div style="margin-top: 20px;">
    <div style="font-size: 9px; font-weight: bold; letter-spacing: 1.5px; color: #00AEEF; text-transform: uppercase;">Payments To</div>
    <div style="font-weight: bold; font-size: 14px;">{{ $vendor->name }}</div>
    @if ($vendor->phone)<div class="muted">{{ $vendor->phone }}</div>@endif
</div>

<table class="rows">
    <thead>
        <tr>
            <th style="width: 80px;">Date</th>
            <th>Description</th>
            <th class="r" style="width: 100px;">Paid (BDT)</th>
            <th class="r" style="width: 130px;">Previous Due Left (BDT)</th>
        </tr>
    </thead>
    <tbody>
        @if ($statement['carryForward'] > 0)
            <tr class="carry">
                <td>{{ $from?->format('d M Y') ?? '—' }}</td>
                <td>Previous balance carried forward</td>
                <td class="r"></td>
                <td class="r">{{ $fmt($statement['carryForward']) }}</td>
            </tr>
        @endif
        @forelse ($statement['rows'] as $row)
            <tr>
                <td>{{ $row['date']->format('d M Y') }}</td>
                <td>{{ $row['description'] }}</td>
                <td class="r">{{ $fmt($row['paid']) }}</td>
                <td class="r">{{ $statement['carryForward'] > 0 ? $fmt($row['previous_remaining']) : '' }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted" style="font-style: italic;">No payments in this period</td></tr>
        @endforelse
        <tr class="totals">
            <td colspan="2">Total paid in period</td>
            <td class="r">{{ $fmt($statement['totalPaid']) }}</td>
            <td class="r"></td>
        </tr>
    </tbody>
</table>

@if ($statement['carryForward'] > 0 || $statement['closing'] > 0)
    <div class="closing" style="color: {{ $statement['closing'] > 0 ? '#C43D3D' : '#1E9E58' }};">
        Previous balance remaining: {{ $fmt($statement['closing']) }} BDT
    </div>
@endif

<div class="muted" style="margin-top: 24px; text-align: center; font-size: 8.5px;">
    Generated on {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
