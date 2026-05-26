@extends('portal.layouts.dashboard')

@section('title', 'Dashboard — TesNet')

@php
    $card = 'bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 shadow-[0_4px_12px_rgba(37,99,235,0.06)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.25)]';
@endphp

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-8 max-w-container-max w-full mr-auto space-y-6">
    <div>
        <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-2 sm:gap-3 mb-2">
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface">
                Hello, {{ $displayName }}!
            </h1>
            @if ($isConnected)
                <div class="flex items-center gap-1 w-fit bg-secondary-container/30 dark:bg-secondary-container/10 text-secondary dark:text-secondary-fixed-dim px-3 py-1.5 rounded-full">
                    <span class="material-symbols-outlined fill text-[18px] sm:text-[20px]">check_circle</span>
                    <span class="font-label-sm text-label-sm">You're connected</span>
                </div>
            @elseif ($hasActivePlan)
                <form method="POST" action="{{ route('portal.connect-wifi') }}" class="w-full sm:w-auto shrink-0">@csrf
                    <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full font-label-sm text-label-sm font-semibold bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed shadow-md ring-2 ring-white/30 dark:ring-white/25 hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
                        <span class="material-symbols-outlined text-[22px]" aria-hidden="true">wifi</span>
                        Connect to Internet
                    </button>
                </form>
            @else
                <a href="{{ route('portal.packages') }}"
                   class="w-full sm:w-auto shrink-0 flex items-center justify-center gap-2 min-h-[44px] px-5 py-2.5 rounded-full font-label-sm text-label-sm font-semibold bg-tertiary text-on-tertiary dark:bg-tertiary-fixed-dim dark:text-on-tertiary-container shadow-md ring-2 ring-white/20 hover:opacity-90 transition-opacity active:scale-[0.98]">
                    <span class="material-symbols-outlined text-[22px]" aria-hidden="true">bolt</span>
                    Buy data to connect
                </a>
            @endif
        </div>
        <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">Welcome back to your Wi-Fi dashboard.</p>
    </div>

@include('portal.partials.announcement-modal', ['announcement' => $announcement ?? null])

    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6">
    {{-- Your Data --}}
    <div class="{{ $card }} md:col-span-8 p-4 sm:p-6 md:p-8 flex flex-col md:flex-row items-center justify-between gap-6 md:gap-8 relative overflow-hidden">
        <div class="absolute -top-20 -right-20 w-48 sm:w-64 h-48 sm:h-64 bg-primary-fixed dark:opacity-20 opacity-50 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex-1 flex flex-col items-center md:items-start text-center md:text-left z-10 w-full min-w-0">
            <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-1">Your Data</h2>
            <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-4 sm:mb-6 line-clamp-2">
                {{ $hasActivePlan ? ($activePackage->package_name ?? 'Current active plan') : 'No active plan' }}
            </p>
            <div class="data-ring-size relative w-36 h-36 sm:w-48 sm:h-48 mb-4 sm:mb-6 flex items-center justify-center shrink-0">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100" aria-hidden="true">
                    <circle class="text-surface-variant dark:text-surface-container-highest" cx="50" cy="50" fill="transparent" r="40" stroke="currentColor" stroke-linecap="round" stroke-width="12"></circle>
                    <circle class="text-primary dark:text-primary-fixed-dim transition-all duration-1000 ease-out" cx="50" cy="50" fill="transparent" r="40" stroke="currentColor"
                        stroke-dasharray="251.2" stroke-dashoffset="{{ $hasActivePlan ? $chartStrokeOffset : 251.2 }}" stroke-linecap="round" stroke-width="12"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center text-center px-2">
                    @if ($hasActivePlan)
                        @if ($isUnlimitedData ?? false)
                            <span class="font-title-md sm:font-headline-lg text-title-md sm:text-headline-lg-mobile text-primary dark:text-primary-fixed-dim">∞</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Unlimited</span>
                        @else
                            <span class="data-display-text font-display-mobile sm:font-display-lg text-display-mobile sm:text-display-lg text-primary dark:text-primary-fixed-dim">{{ $dataRemainingGb }}</span>
                            <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">GB left</span>
                        @endif
                    @else
                        <span class="font-title-md text-title-md text-on-surface-variant dark:text-outline-variant">—</span>
                        <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">No plan</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="flex-1 flex flex-col items-stretch md:items-end justify-center z-10 w-full min-w-0">
            <div class="bg-surface-container-high dark:bg-on-background/50 p-3 sm:p-4 rounded-xl mb-4 w-full md:max-w-sm">
                <div class="flex justify-between items-center mb-2 gap-2">
                    <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Total Plan</span>
                    <span class="font-label-sm text-label-sm text-on-background dark:text-inverse-on-surface shrink-0">{{ $hasActivePlan ? (($isUnlimitedData ?? false) ? 'Unlimited' : $totalPlanGb.' GB') : '—' }}</span>
                </div>
                <div class="flex justify-between items-center gap-2">
                    <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Validity</span>
                    <span class="font-label-sm text-label-sm text-on-background dark:text-inverse-on-surface shrink-0 text-right">{{ $hasActivePlan ? $validityLabel : '—' }}</span>
                </div>
            </div>
            <a href="{{ route('portal.packages') }}"
               class="w-full md:max-w-sm min-h-[48px] sm:min-h-[56px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm flex items-center justify-center gap-2 hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98] shadow-sm">
                <span class="material-symbols-outlined">add_circle</span>
                Quick Recharge
            </a>
        </div>
    </div>

    <div class="md:col-span-4 flex flex-col gap-4 md:gap-6">
        {{-- WiFi Speed --}}
        <div class="{{ $card }} p-4 sm:p-6">
            <div class="flex items-center justify-between mb-3 sm:mb-4 gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="bg-primary-container/30 dark:bg-primary-container/10 text-primary dark:text-primary-fixed-dim p-2 rounded-lg shrink-0">
                        <span class="material-symbols-outlined">wifi</span>
                    </div>
                    <h3 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface truncate">WiFi Speed</h3>
                </div>
                @if ($isConnected && $wifiSpeedMbps)
                    <span class="material-symbols-outlined fill text-secondary dark:text-secondary-fixed-dim text-2xl sm:text-3xl shrink-0">mood</span>
                @endif
            </div>
            <div class="flex items-end gap-2">
                <span class="font-headline-lg-mobile sm:font-headline-lg text-headline-lg-mobile sm:text-headline-lg text-on-background dark:text-inverse-on-surface">{{ $wifiSpeedMbps ?? ($isConnected ? '—' : '0') }}</span>
                <span class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-0.5">Mbps</span>
            </div>
            <p class="font-label-sm text-label-sm text-secondary dark:text-secondary-fixed-dim mt-2 flex items-start gap-1">
                @if ($isConnected)
                    <span class="material-symbols-outlined text-[16px] shrink-0 mt-0.5">arrow_upward</span>
                    <span>{{ $wifiSpeedMbps ? 'Optimal connection for studying' : 'Connected — speed cap not set' }}</span>
                @else
                    Connect to WiFi to see live speed
                @endif
            </p>
        </div>

    </div>

    {{-- About hotspot --}}
    <div class="md:col-span-12 bg-surface-container-low dark:bg-inverse-surface rounded-xl p-4 sm:p-6 md:p-8 border border-surface-variant/30 dark:border-outline/30 grid grid-cols-1 md:grid-cols-[1fr_auto] items-start md:items-center gap-4 md:gap-6 shadow-sm">
        <div class="min-w-0 w-full max-w-none">
            <h3 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mb-2">About the hotspot</h3>
            <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
                Learn how to connect, how your data & speed work, and quick troubleshooting steps.
            </p>
        </div>
        <a href="{{ route('portal.about') }}"
           class="w-full md:w-auto min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg px-6 py-3 font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98] inline-flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[20px]">info</span>
            Open
        </a>
    </div>
    </div>
</div>
@endsection

@push('scripts')
@if (file_exists(public_path('assets/portal/portal-announcements.js')))
    <script src="{{ asset('assets/portal/portal-announcements.js') }}" defer></script>
@elseif (file_exists(public_path('build/manifest.json')))
    @vite(['resources/js/portal-announcements.js'])
@endif
@endpush
