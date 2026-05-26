@extends('portal.layouts.dashboard')

@section('title', 'About Hotspot — TesNet')

@php
    $card = 'bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 shadow-[0_4px_12px_rgba(37,99,235,0.06)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.25)]';
@endphp

@section('content')
<div class="px-4 md:px-margin-desktop py-6 md:py-10 max-w-container-max w-full mr-auto space-y-4 md:space-y-6">
    <div>
        <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface">
            About the Hotspot
        </h1>
        <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mt-1">
            How to connect, how billing works, and what to do if you have issues.
        </p>
    </div>

    <div class="{{ $card }} p-4 sm:p-6 md:p-8">
        <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-3">How to connect</h2>
        <ol class="space-y-2 list-decimal list-inside font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
            <li>Turn on Wi‑Fi and connect to the campus hotspot network.</li>
            <li>Open your browser — you’ll be redirected to the login page.</li>
            <li>Sign in with your phone number and password.</li>
            <li>Buy data if you don’t have an active plan.</li>
        </ol>

        @if (!empty($loginUrl))
            <div class="mt-4 rounded-lg border border-outline-variant/30 dark:border-outline/30 bg-surface-container-high dark:bg-on-background/50 p-4">
                <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-1">Hotspot login URL</p>
                <p class="font-body-md text-body-md text-on-background dark:text-inverse-on-surface break-all">{{ $loginUrl }}</p>
            </div>
        @endif
    </div>

    <div class="{{ $card }} p-4 sm:p-6 md:p-8">
        <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-3">How data & speed work</h2>
        <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
            <li>Your plan stays active <span class="font-semibold text-on-background dark:text-inverse-on-surface">until your data finishes</span>.</li>
            <li>Speed is capped based on your purchase (packages have their own caps; custom purchases use the custom cap).</li>
            <li>If you buy a new plan, it replaces the previous active plan.</li>
        </ul>
    </div>

    <div class="{{ $card }} p-4 sm:p-6 md:p-8">
        <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-3">Troubleshooting</h2>
        <ul class="space-y-2 font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
            <li>If you can’t browse after login, disconnect and reconnect to Wi‑Fi, then try again.</li>
            <li>If multiple devices can’t connect, you may have reached your device limit.</li>
            <li>If you made payment but data didn’t apply, wait 1–2 minutes and refresh your dashboard.</li>
        </ul>
        <div class="mt-5">
            <a href="{{ route('portal.packages') }}"
               class="inline-flex items-center justify-center gap-2 min-h-[44px] px-5 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
                <span class="material-symbols-outlined">bolt</span>
                Buy data
            </a>
        </div>
    </div>
</div>
@endsection

