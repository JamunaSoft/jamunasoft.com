@props([
    'name',
    'label',
    'required' => false,
    'hint' => null,
    'accept' => '.pdf,.doc,.docx,.jpg,.jpeg,.png',
])

<div {{ $attributes->only('class') }}>
    <label for="field-{{ $name }}" class="block text-sm font-semibold text-navy-900">
        {{ $label }}
        @if ($required)<span class="text-red-500" aria-hidden="true">*</span>@endif
    </label>
    <input
        type="file"
        id="field-{{ $name }}"
        name="{{ $name }}"
        accept="{{ $accept }}"
        @if ($required) required @endif
        @if ($hint || $errors->has($name)) aria-describedby="{{ $errors->has($name) ? "error-$name" : "hint-$name" }}" @endif
        @error($name) aria-invalid="true" @enderror
        class="mt-1.5 block w-full rounded-xl border bg-white px-4 py-2 text-sm text-slate-600 shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700 hover:file:bg-brand-100 focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none {{ $errors->has($name) ? 'border-red-400' : 'border-slate-300' }}"
    />
    @if ($hint && ! $errors->has($name))
        <p id="hint-{{ $name }}" class="mt-1.5 text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @error($name)
        <p id="error-{{ $name }}" class="mt-1.5 text-xs font-medium text-red-600">{{ $message }}</p>
    @enderror
</div>
