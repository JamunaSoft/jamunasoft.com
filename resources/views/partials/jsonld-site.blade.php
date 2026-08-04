@php
    $companyName = settings('company_name', 'Jamuna Soft');
    $logoPath = settings('logo_path');

    $sameAs = collect(cache()->remember(
        'site_social_links',
        now()->addHour(),
        fn (): array => \App\Models\SocialLink::query()->active()->ordered()->get()
            ->map(fn ($link) => ['platform' => $link->platform, 'label' => $link->label, 'url' => $link->url])
            ->all(),
    ))->pluck('url')->filter()->values()->all();

    $organization = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $companyName,
        'legalName' => settings('legal_name'),
        'url' => url('/'),
        'logo' => $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoPath) : null,
        'email' => settings('email_primary'),
        'telephone' => settings('phone_primary'),
        'address' => settings('office_address') ? [
            '@type' => 'PostalAddress',
            'streetAddress' => settings('office_address'),
            'addressCountry' => 'BD',
        ] : null,
        'sameAs' => $sameAs ?: null,
    ]);

    $website = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => settings('site_title', $companyName),
        'url' => url('/'),
    ];
@endphp

<script type="application/ld+json">@json($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
<script type="application/ld+json">@json($website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
