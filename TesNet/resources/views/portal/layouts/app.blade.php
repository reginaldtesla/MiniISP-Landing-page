<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TesNet Portal')</title>
    @include('portal.partials.assets')
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-slate-200 text-slate-800">
    <nav class="border-b border-slate-200 bg-white/90 backdrop-blur sticky top-0 z-40">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-2 px-4 py-3">
            <a href="{{ route('portal.dashboard') }}" class="text-lg font-bold text-primary">TesNet</a>
            @auth
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <a href="{{ route('portal.dashboard') }}" class="hover:text-primary">Dashboard</a>
                    <a href="{{ route('portal.packages') }}" class="hover:text-primary">Buy Data</a>
                    <a href="{{ route('portal.support.index') }}" class="hover:text-primary">Support</a>
                    <a href="{{ route('portal.about') }}" class="hover:text-primary">About Hotspot</a>
                    <a href="{{ route('portal.payments.history') }}" class="hover:text-primary">Payments</a>
                    @include('portal.partials.logout-button', [
                        'class' => 'inline',
                        'buttonClass' => 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-error hover:bg-red-50 dark:hover:bg-error-container/20 transition-colors',
                        'label' => 'Log out',
                    ])
                </div>
            @endauth
        </div>
    </nav>
    <main class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-inside list-disc text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
