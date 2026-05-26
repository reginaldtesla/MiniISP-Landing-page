@include('portal.partials.theme-init')
@if (file_exists(public_path('assets/portal/portal.css')))
    <link rel="stylesheet" href="{{ asset('assets/portal/portal.css') }}">
    <script src="{{ asset('assets/portal/portal-theme.js') }}" defer></script>
@elseif (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/portal.css', 'resources/js/portal-theme.js'])
@endif
