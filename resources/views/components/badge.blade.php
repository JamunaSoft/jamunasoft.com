@props(['color' => 'brand'])

@php
    $colors = [
        'brand' => 'bg-brand-50 text-brand-700',
        'navy' => 'bg-navy-100 text-navy-800',
        'cyan' => 'bg-cyan-50 text-cyan-700',
        'green' => 'bg-emerald-50 text-emerald-700',
        'slate' => 'bg-slate-100 text-slate-700',
        'light' => 'bg-white/10 text-white',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold '.($colors[$color] ?? $colors['brand'])]) }}>{{ $slot }}</span>
