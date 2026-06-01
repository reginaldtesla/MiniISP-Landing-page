@php
    /** @var string $file Offline bundle filename, e.g. portal-announcements.js */
    $file = $file ?? '';
    $viteEntry = \App\Support\PortalAssets::viteEntry($file);
@endphp
@if ($url = \App\Support\PortalAssets::assetUrl($file))
    <script src="{{ $url }}" defer></script>
@elseif (! \App\Support\PortalAssets::useOffline() && $viteEntry && file_exists(public_path('build/manifest.json')))
    @vite([$viteEntry])
@endif
