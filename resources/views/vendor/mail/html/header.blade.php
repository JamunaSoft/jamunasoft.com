@php
    $logoSetting = settings('logo_path');
    $logoUrl = $logoSetting
        ? url(\Illuminate\Support\Facades\Storage::disk('public')->url($logoSetting))
        : url('assets/logo.png');
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ $logoUrl }}" class="logo" alt="{{ settings('company_name', config('app.name')) }}" style="height: 46px; max-height: 46px; width: auto;">
</a>
</td>
</tr>
