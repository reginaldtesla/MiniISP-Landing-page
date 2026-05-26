<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <title>@yield('title', 'TESNET Admin Hub')</title>
    @include('portal.partials.design-system')
    <style>
        .soft-shadow { box-shadow: 0 4px 12px rgba(0, 74, 198, 0.06); }
        html.dark .soft-shadow { box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); }
    </style>
</head>
@php
    $routeName = request()->route()?->getName() ?? '';
    $isDashboard = str_contains($routeName, 'admin.dashboard');
    $isPackages = str_contains($routeName, 'admin.packages');
    $isAnnouncements = str_contains($routeName, 'admin.notifications');
    $isSettings = str_contains($routeName, 'admin.users') || str_contains($routeName, 'admin.sessions');
    $navActive = 'flex items-center gap-3 px-4 py-3 rounded-lg text-primary dark:text-primary-fixed-dim font-bold border-r-4 border-primary dark:border-primary-fixed-dim bg-primary-container/20 dark:bg-primary-container/30';
    $navIdle = 'flex items-center gap-3 px-4 py-3 rounded-lg text-on-surface-variant dark:text-outline-variant border-r-4 border-transparent hover:bg-surface-container-highest dark:hover:bg-admin-elevated-high';
@endphp
<body class="flex flex-col md:flex-row min-h-screen min-h-[100dvh] overflow-hidden bg-background dark:bg-inverse-surface text-on-background dark:text-inverse-on-surface antialiased">

<header class="md:hidden flex justify-between items-center w-full px-4 h-14 shrink-0 z-30 bg-surface dark:bg-inverse-surface border-b border-outline-variant/30 fixed top-0 left-0 right-0">
    <div class="flex items-center gap-2 min-w-0">
        <div class="w-8 h-8 rounded-md bg-inverse-surface dark:bg-admin-elevated-high overflow-hidden shrink-0">
            <img alt="TESNET" class="w-full h-full object-cover" src="{{ asset('images/tesnet.png') }}"/>
        </div>
        <h1 class="font-title-md text-title-md font-bold text-primary dark:text-primary-fixed-dim truncate">TESNET</h1>
    </div>
    <div class="flex items-center gap-1 shrink-0">
        @include('portal.partials.theme-toggle')
        @include('portal.partials.logout-button', [
            'action' => route('admin.logout'),
            'class' => 'inline',
            'buttonClass' => 'flex items-center gap-1.5 min-h-[44px] px-3 rounded-lg font-label-sm text-label-sm text-error dark:text-red-400 hover:bg-error-container/30 transition-colors active:scale-[0.98]',
            'label' => 'Log out',
        ])
    </div>
</header>

<nav class="admin-sidebar hidden md:flex fixed top-0 bottom-0 left-0 z-30 w-64 flex-col bg-surface-container-low dark:bg-on-background shadow-md border-r border-outline-variant/30 dark:border-outline/20">
    <div class="shrink-0 p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden bg-inverse-surface dark:bg-admin-elevated-high shrink-0">
            <img alt="TESNET Logo" class="w-full h-full object-cover" src="{{ asset('images/tesnet.png') }}"/>
        </div>
        <div class="min-w-0">
            <h2 class="font-title-md text-title-md text-primary dark:text-primary-fixed-dim font-bold">TESNET</h2>
            <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant/90">Admin Hub</p>
        </div>
    </div>
    <div class="flex flex-col gap-1 px-4 flex-1 min-h-0 overflow-y-auto mt-2">
        <a href="{{ route('admin.dashboard') }}" class="{{ $isDashboard ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined">grid_view</span>
            <span class="font-body-md text-body-md font-medium">Dashboard</span>
        </a>
        <a href="{{ route('admin.packages.index') }}" class="{{ $isPackages ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined">inventory_2</span>
            <span class="font-body-md text-body-md font-medium">Packages</span>
        </a>
        <a href="{{ route('admin.notifications.index') }}" class="{{ $isAnnouncements ? $navActive : $navIdle }}">
            <span class="material-symbols-outlined {{ $isAnnouncements ? 'fill' : '' }}">campaign</span>
            <span class="font-body-md text-body-md font-medium">Announcements</span>
        </a>
        <a href="{{ route('admin.users.index') }}" class="{{ $isSettings ? $navActive : $navIdle }} mt-auto">
            <span class="material-symbols-outlined">settings</span>
            <span class="font-body-md text-body-md font-medium">Settings</span>
        </a>
    </div>
    <div class="shrink-0 mt-auto p-4 border-t border-outline-variant/30 dark:border-outline/20 space-y-3">
        <div class="flex items-center justify-between px-1">
            <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Theme</span>
            @include('portal.partials.theme-toggle')
        </div>
        @include('portal.partials.logout-button', ['action' => route('admin.logout')])
    </div>
</nav>

<div class="flex flex-1 flex-col min-h-0 min-h-[calc(100dvh-3.5rem)] w-full overflow-hidden pt-14 pb-[calc(4.5rem+env(safe-area-inset-bottom))] md:ml-64 md:h-[100dvh] md:overflow-y-auto md:pt-0 md:pb-0">
    @if (session('status'))
        <div class="shrink-0 mx-4 mt-3 md:mx-6 md:mt-4 rounded-lg border border-secondary-container bg-secondary-container/30 dark:bg-secondary-container/10 px-4 py-2.5 text-secondary dark:text-secondary-fixed-dim font-body-md text-sm flex items-center gap-2">
            <span class="material-symbols-outlined fill text-[18px]">check_circle</span>
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="shrink-0 mx-4 mt-3 md:mx-6 rounded-lg border border-error-container bg-error-container/40 px-4 py-2.5 text-error font-body-md text-sm">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif
    @yield('content')
</div>

<nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 flex justify-around items-center px-2 pt-2 bg-surface dark:bg-inverse-surface border-t border-outline-variant/30 rounded-t-xl pb-[max(0.5rem,env(safe-area-inset-bottom))] shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
    @php
        $mobActive = 'flex flex-col items-center justify-center bg-primary-container/30 dark:bg-primary-container/20 text-primary dark:text-primary-fixed-dim rounded-xl px-3 py-2 min-w-[72px] active:scale-95';
        $mobIdle = 'flex flex-col items-center justify-center text-on-surface-variant dark:text-outline-variant p-2 min-w-[72px] active:scale-95';
    @endphp
    <a href="{{ route('admin.dashboard') }}" class="{{ $isDashboard ? $mobActive : $mobIdle }}">
        <span class="material-symbols-outlined text-[22px]">grid_view</span>
        <span class="font-label-sm text-[11px] mt-0.5">Home</span>
    </a>
    <a href="{{ route('admin.notifications.index') }}" class="{{ $isAnnouncements ? $mobActive : $mobIdle }}">
        <span class="material-symbols-outlined text-[22px] {{ $isAnnouncements ? 'fill' : '' }}">campaign</span>
        <span class="font-label-sm text-[11px] mt-0.5">Announce</span>
    </a>
    <a href="{{ route('admin.users.index') }}" class="{{ $isSettings ? $mobActive : $mobIdle }}">
        <span class="material-symbols-outlined text-[22px]">settings</span>
        <span class="font-label-sm text-[11px] mt-0.5">Settings</span>
    </a>
    <form method="POST" action="{{ route('admin.logout') }}" class="flex min-w-[72px]">
        @csrf
        <button type="submit" class="flex flex-col items-center justify-center text-error dark:text-red-400 p-2 min-w-[72px] active:scale-95" aria-label="Log out">
            <span class="material-symbols-outlined text-[22px]">logout</span>
            <span class="font-label-sm text-[11px] mt-0.5">Log out</span>
        </button>
    </form>
</nav>

@stack('scripts')
</body>
</html>
