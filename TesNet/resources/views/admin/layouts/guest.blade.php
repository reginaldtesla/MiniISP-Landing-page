<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover"/>
    <title>@yield('title', 'Admin — TESNET')</title>
    @include('portal.partials.design-system')
</head>
<body class="bg-background dark:bg-inverse-surface text-on-background dark:text-inverse-on-surface min-h-screen min-h-[100dvh] flex flex-col antialiased overflow-x-hidden">
    <div class="flex justify-end px-4 pt-4 safe-top">
        @include('portal.partials.theme-toggle')
    </div>
    <div class="flex-1 flex items-center justify-center p-4 pb-8 w-full max-w-md mx-auto">
        <div class="w-full">
            <div class="text-center mb-6 sm:mb-8">
                <img alt="TESNET" class="h-16 sm:h-20 mx-auto rounded-xl mb-3" src="{{ asset('images/tesnet.png') }}"/>
                <p class="font-label-sm text-label-sm text-primary dark:text-primary-fixed-dim uppercase tracking-wider">Admin Hub</p>
            </div>
            @include('portal.partials.alerts')
            @yield('content')
        </div>
    </div>
</body>
</html>
