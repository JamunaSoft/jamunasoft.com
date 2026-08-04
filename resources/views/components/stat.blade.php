@props(['value', 'label', 'light' => false])

<div {{ $attributes->merge(['class' => 'text-center']) }}>
    <p class="text-3xl md:text-4xl font-bold {{ $light ? 'text-gradient' : 'text-navy-900' }}">{{ $value }}</p>
    <p class="mt-1 text-sm font-medium {{ $light ? 'text-slate-300' : 'text-slate-500' }}">{{ $label }}</p>
</div>
