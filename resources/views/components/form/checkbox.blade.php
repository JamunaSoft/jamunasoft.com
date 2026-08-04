@props([
    'name',
    'label',
    'value' => '1',
    'required' => false,
    'checked' => false,
])

@php
    $fieldId = 'field-'.str_replace(['[', ']'], ['-', ''], $name).'-'.\Illuminate\Support\Str::slug($value);
    $baseName = str_contains($name, '[]') ? str_replace('[]', '', $name) : $name;
    $oldValue = old($baseName);
    $isChecked = str_contains($name, '[]')
        ? is_array($oldValue) && in_array($value, $oldValue)
        : ($oldValue !== null ? (bool) $oldValue : $checked);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex items-start gap-2.5']) }}>
    <input
        type="checkbox"
        id="{{ $fieldId }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @if ($required) required @endif
        @checked($isChecked)
        @error($baseName) aria-invalid="true" @enderror
        class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
    />
    <div>
        <label for="{{ $fieldId }}" class="text-sm text-slate-700">{{ $label }}</label>
        @error($baseName)
            <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
