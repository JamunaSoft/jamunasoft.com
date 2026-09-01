@if (session()->has('impersonator_id'))
    <div style="background: #b45309; color: #ffffff; padding: 10px 16px; text-align: center; font-size: 14px;">
        You are viewing the client panel as <strong>{{ auth()->user()?->name }}</strong> —
        <a href="{{ route('impersonate.leave') }}" style="color: #ffffff; text-decoration: underline; font-weight: 600;">Return to admin</a>
    </div>
@endif
