@props([
    'name',
    'label',
    'required' => false,
    'rows' => 5,
    'value' => null,
    'hint' => null,
])

<div {{ $attributes->only('class') }}>
    <label for="field-{{ $name }}" class="block text-sm font-semibold text-navy-900">
        {{ $label }}
        @if ($required)<span class="text-red-500" aria-hidden="true">*</span>@endif
    </label>
    <textarea
        id="field-{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($required) required @endif
        @if ($hint || $errors->has($name)) aria-describedby="{{ $errors->has($name) ? "error-$name" : "hint-$name" }}" @endif
        @error($name) aria-invalid="true" @enderror
        {{ $attributes->except('class')->merge(['class' => 'mt-1.5 block w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-navy-900 shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none '.($errors->has($name) ? 'border-red-400' : 'border-slate-300')]) }}
    >{{ old($name, $value) }}</textarea>
    @if ($hint && ! $errors->has($name))
        <p id="hint-{{ $name }}" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="error-{{ $name }}" class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
