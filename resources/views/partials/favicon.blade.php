{{-- Dynamic browser tab icon: favicon upload → store logo → branded default --}}
@php
    $faviconUrl = site_favicon_url($settings ?? null);
    $faviconPath = data_get($settings ?? null, 'favicon_path') ?: data_get($settings ?? null, 'logo_path');
    $faviconVer = $faviconPath
        ? (@filemtime(public_storage_path($faviconPath)) ?: time())
        : time();
    if ($faviconUrl && ! str_contains($faviconUrl, '?')) {
        $faviconUrl .= '?v='.$faviconVer;
    }
    $applePath = 'cms/favicon/akhitelecom-apple.png';
    $appleFull = public_storage_path($applePath);
    $appleUrl = is_file($appleFull)
        ? public_storage_url($applePath).'?v='.(@filemtime($appleFull) ?: $faviconVer)
        : $faviconUrl;
    $faviconType = str_ends_with(strtolower(parse_url($faviconUrl, PHP_URL_PATH) ?? ''), '.svg')
        ? 'image/svg+xml'
        : 'image/png';
@endphp
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="32x32">
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="48x48">
<link rel="icon" href="{{ $faviconUrl }}" type="{{ $faviconType }}" sizes="192x192">
<link rel="shortcut icon" href="{{ $faviconUrl }}">
<link rel="apple-touch-icon" href="{{ $appleUrl }}" sizes="180x180">
