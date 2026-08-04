@props(['label' => null])

<div {{ $attributes->merge(['class' => 'relative flex items-center justify-center overflow-hidden bg-gradient-to-br from-navy-900 via-brand-700 to-accent-500']) }} aria-hidden="true">
    <svg class="absolute inset-0 h-full w-full opacity-20" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
        <circle cx="60" cy="60" r="120" stroke="white" stroke-width="1.5" />
        <circle cx="340" cy="240" r="150" stroke="white" stroke-width="1.5" />
        <circle cx="200" cy="150" r="80" stroke="white" stroke-width="1" />
    </svg>
    <span class="relative text-2xl font-bold tracking-tight text-white/70 select-none">{{ $label ?? 'JS' }}</span>
</div>
