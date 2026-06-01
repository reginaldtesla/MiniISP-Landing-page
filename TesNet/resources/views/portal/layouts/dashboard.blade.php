<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <meta name="theme-color" content="#004ac6" media="(prefers-color-scheme: light)"/>
    <meta name="theme-color" content="#213145" media="(prefers-color-scheme: dark)"/>
    <title>@yield('title', 'TesNet Dashboard')</title>
    @include('portal.partials.design-system')
</head>
@php
    use App\Models\RadAcct;
    $routeName = request()->route()?->getName() ?? '';
    $isHome = str_starts_with($routeName, 'portal.dashboard');
    $isBuyData = str_starts_with($routeName, 'portal.packages') || str_starts_with($routeName, 'portal.payments');
    $isAbout = str_starts_with($routeName, 'portal.about');
    $isSupport = str_starts_with($routeName, 'portal.support');
    $isDevices = str_starts_with($routeName, 'portal.devices');
    $user = auth()->user();
    $sidebarName = $user->name && $user->name !== $user->phone_number && ! str_contains($user->name, '@')
        ? explode(' ', trim($user->name))[0]
        : (($user->phone_number ?? '') ?: 'User');
    $isConnected = $user->phone_number
        ? RadAcct::query()->active()->where('username', $user->phone_number)->exists()
        : false;
    $navActive = 'flex items-center gap-3 px-4 py-3 rounded-lg font-label-sm text-label-sm text-primary dark:text-primary-fixed-dim font-bold border-r-4 border-primary dark:border-primary-fixed-dim bg-primary-container/20 dark:bg-primary-container/10';
    $navIdle = 'flex items-center gap-3 px-4 py-3 rounded-lg font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant border-r-4 border-transparent hover:bg-surface-container-highest dark:hover:bg-surface-variant/30';
@endphp
<body class="bg-background dark:bg-inverse-surface text-on-background dark:text-inverse-on-surface min-h-screen min-h-[100dvh] flex flex-col md:overflow-hidden antialiased">

{{-- Mobile header --}}
<header class="md:hidden sticky top-0 z-40 shrink-0 flex justify-between items-center w-full px-4 h-14 bg-surface/95 dark:bg-on-background/95 backdrop-blur-md border-b border-outline-variant/30 dark:border-outline/20">
    <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 min-h-[44px]">
        <img alt="" class="h-8 w-8 rounded-lg object-contain" src="{{ asset('images/tesnet.png') }}"/>
        <span class="font-headline-lg-mobile text-headline-lg-mobile font-bold text-primary dark:text-primary-fixed-dim">TESNET</span>
    </a>
    <div class="flex items-center gap-1">
        @include('portal.partials.theme-toggle')
        @include('portal.partials.logout-button', [
            'class' => 'inline',
            'buttonClass' => 'flex items-center gap-1.5 min-h-[44px] px-3 rounded-lg font-label-sm text-label-sm text-error dark:text-red-400 hover:bg-error-container/30 dark:hover:bg-error-container/20 transition-colors active:scale-[0.98]',
            'label' => 'Log out',
        ])
    </div>
</header>

{{-- Desktop sidebar --}}
<nav class="portal-sidebar bg-surface-container-low dark:bg-on-background shadow-md hidden md:flex fixed top-0 bottom-0 left-0 z-40 w-64 flex-col border-r border-outline-variant/20 dark:border-outline/20">
    <div class="shrink-0 p-5 flex items-center justify-center">
        <a href="{{ route('portal.dashboard') }}">
            <img alt="TESNET Logo" class="h-20 w-auto object-contain rounded-xl" src="{{ asset('images/tesnet.png') }}"/>
        </a>
    </div>
    <div class="flex flex-col gap-1 px-3 flex-1 min-h-0 overflow-y-auto">
        <a href="{{ route('portal.dashboard') }}" class="{{ $isHome ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined {{ $isHome ? 'fill' : '' }}">home</span><span>Home</span>
        </a>
        <a href="{{ route('portal.packages') }}" class="{{ $isBuyData ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined">bolt</span><span>Buy Data</span>
        </a>
        <a href="{{ route('portal.devices.index') }}" class="{{ $isDevices ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined">devices</span><span>Devices</span>
        </a>
        <a href="{{ route('portal.support.index') }}" class="{{ $isSupport ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined">support_agent</span><span>Support</span>
        </a>
        <a href="{{ route('portal.about') }}" class="{{ $isAbout ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined">info</span><span>About Hotspot</span>
        </a>
    </div>
    <div class="shrink-0 mt-auto p-4 border-t border-outline-variant/30 dark:border-outline/20 space-y-3">
        <div class="flex items-center justify-between px-2">
            <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Theme</span>
            @include('portal.partials.theme-toggle')
        </div>
        <div class="flex items-center gap-3 px-2 mb-3">
            <div class="w-10 h-10 shrink-0 rounded-full bg-primary-container dark:bg-primary-container/30 flex items-center justify-center text-on-primary dark:text-primary-fixed-dim font-bold text-sm">
                {{ strtoupper(substr($sidebarName, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="font-body-md text-body-md text-on-surface dark:text-inverse-on-surface font-semibold truncate">{{ $sidebarName }}</p>
                <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant flex items-center gap-1">
                    @if ($isConnected)
                        <span class="w-2 h-2 rounded-full bg-secondary dark:bg-secondary-fixed-dim shrink-0"></span> Connected
                    @else
                        <span class="w-2 h-2 rounded-full bg-outline-variant shrink-0"></span> Offline
                    @endif
                </p>
            </div>
        </div>
        <div class="px-2">
            @include('portal.partials.logout-button')
        </div>
    </div>
</nav>

<main class="flex-1 w-full min-w-0 overflow-y-auto overflow-x-hidden pb-[calc(5.5rem+env(safe-area-inset-bottom))] md:ml-64 md:h-[100dvh] md:pb-8">
    @include('portal.partials.outage-banner')
    @include('portal.partials.alerts')
    @yield('content')
</main>

{{-- Mobile bottom nav --}}
<nav class="fixed bottom-0 left-0 right-0 z-50 md:hidden flex justify-around items-stretch px-1 pt-2 border-t border-outline-variant dark:border-outline/40 bg-surface/95 dark:bg-on-background/95 backdrop-blur-md shadow-[0_-4px_20px_rgba(0,0,0,0.08)] dark:shadow-[0_-4px_24px_rgba(0,0,0,0.35)] rounded-t-2xl pb-[max(0.5rem,env(safe-area-inset-bottom))]" aria-label="Main navigation">
    @php
        $bottomActive = 'flex flex-1 flex-col items-center justify-center gap-0.5 min-h-[52px] rounded-xl py-1.5 bg-primary-container/30 text-primary dark:bg-primary-container/20 dark:text-primary-fixed-dim transition-all active:scale-95';
        $bottomIdle = 'flex flex-1 flex-col items-center justify-center gap-0.5 min-h-[52px] rounded-xl py-1.5 text-on-surface-variant dark:text-outline-variant transition-all active:scale-95';
    @endphp
    <a href="{{ route('portal.dashboard') }}" class="{{ $isHome ? $bottomActive : $bottomIdle }}">
        <span class="material-symbols-outlined text-[22px] {{ $isHome ? 'fill' : '' }}">home</span>
        <span class="font-label-sm text-[11px]">Home</span>
    </a>
    <a href="{{ route('portal.packages') }}" class="{{ $isBuyData ? $bottomActive : $bottomIdle }}">
        <span class="material-symbols-outlined text-[22px]">bolt</span>
        <span class="font-label-sm text-[11px]">Data</span>
    </a>
    <a href="{{ route('portal.support.index') }}" class="{{ $isSupport ? $bottomActive : $bottomIdle }}">
        <span class="material-symbols-outlined text-[22px]">support_agent</span>
        <span class="font-label-sm text-[11px]">Support</span>
    </a>
    <a href="{{ route('portal.about') }}" class="{{ $isAbout ? $bottomActive : $bottomIdle }}">
        <span class="material-symbols-outlined text-[22px]">info</span>
        <span class="font-label-sm text-[11px]">About</span>
    </a>
</nav>

@stack('scripts')
</body>
</html>
