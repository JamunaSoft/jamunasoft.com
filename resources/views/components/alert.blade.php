@props(['type' => 'info'])

@php
    $styles = [
        'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'info' => 'bg-brand-50 border-brand-200 text-brand-800',
        'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
    ];
@endphp

<div role="status" {{ $attributes->merge(['class' => 'rounded-2xl border px-4 py-3 text-sm font-medium '.($styles[$type] ?? $styles['info'])]) }}>
    {{ $slot }}
</div>
