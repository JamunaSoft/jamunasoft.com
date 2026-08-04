@php
    $seo = $seo ?? [];

    $siteTitle = settings_t('site_title', settings_t('company_name', 'Jamuna Soft'));
    $tagline = settings_t('tagline');

    $pageTitle = $seo['title'] ?? null;

    if ($pageTitle && empty($seo['full_title'])) {
        $fullTitle = $pageTitle.' | '.$siteTitle;
    } elseif ($pageTitle) {
        $fullTitle = $pageTitle;
    } else {
        $fullTitle = settings_t('seo_default_title', $siteTitle.($tagline ? ' — '.$tagline : ''));
    }

    $description = $seo['description'] ?? settings_t('seo_default_description');
    $description = $description ? str(strip_tags((string) $description))->limit(300)->toString() : null;

    $canonical = $seo['canonical'] ?? url()->current();

    $ogImage = $seo['image'] ?? null;

    if (! $ogImage && ($ogPath = settings('og_image_path'))) {
        $ogImage = \Illuminate\Support\Facades\Storage::disk('public')->url($ogPath);
    }

    $isPaginated = (int) request()->query('page', 1) > 1;
    $robots = ! empty($seo['noindex']) ? 'noindex' : ($isPaginated ? 'noindex,follow' : null);
@endphp

<title>{{ $fullTitle }}</title>
@if ($description)
    <meta name="description" content="{{ $description }}" />
@endif
<link rel="canonical" href="{{ $canonical }}" />
@if ($robots)
    <meta name="robots" content="{{ $robots }}" />
@endif

<meta property="og:site_name" content="{{ $siteTitle }}" />
<meta property="og:title" content="{{ $fullTitle }}" />
@if ($description)
    <meta property="og:description" content="{{ $description }}" />
@endif
<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}" />
<meta property="og:url" content="{{ $canonical }}" />
<meta property="og:locale" content="{{ app()->getLocale() === 'bn' ? 'bn_BD' : 'en_US' }}" />
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="{{ $ogImage }}" />
@else
    <meta name="twitter:card" content="summary" />
@endif
<meta name="twitter:title" content="{{ $fullTitle }}" />
@if ($description)
    <meta name="twitter:description" content="{{ $description }}" />
@endif
