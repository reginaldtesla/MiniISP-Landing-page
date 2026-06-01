@include('portal.partials.theme-init')
@if (\App\Support\PortalAssets::useOffline())
    <link rel="stylesheet" href="{{ asset('assets/portal/portal.css') }}">
    <script src="{{ asset('assets/portal/portal-theme.js') }}" defer></script>
@elseif (app()->environment('local') && file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/portal.css', 'resources/js/portal-theme.js'])
@else
    {{-- Production: run npm run build:offline on the server --}}
    <link rel="stylesheet" href="{{ asset('assets/portal/portal.css') }}">
    <script src="{{ asset('assets/portal/portal-theme.js') }}" defer></script>
@endif
