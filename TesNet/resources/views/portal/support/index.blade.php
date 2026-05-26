@extends('portal.layouts.dashboard')

@section('title', 'Support — TesNet')

@php
    $card = 'bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 shadow-[0_4px_12px_rgba(37,99,235,0.06)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.25)]';
    $supportPhone = config('portal.support.phone');
    $supportEmail = config('portal.support.email');
    $supportHours = config('portal.support.hours');
@endphp

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto space-y-6">
    <div>
        <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface">
            Support
        </h1>
        <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mt-1">
            Contact our team directly by phone or email. We do not accept messages through this website.
        </p>
    </div>

    <div class="{{ $card }} p-4 sm:p-6 md:p-8 max-w-xl">
        <div class="flex items-center gap-3 mb-5">
            <div class="bg-primary-container/30 dark:bg-primary-container/10 text-primary dark:text-primary-fixed-dim p-2.5 rounded-lg shrink-0">
                <span class="material-symbols-outlined">contact_support</span>
            </div>
            <h2 class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface">Contact us</h2>
        </div>

        <ul class="space-y-4 font-body-md text-body-md">
            @if ($supportPhone)
                <li class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim text-[22px] shrink-0">call</span>
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-0.5">Phone</p>
                        <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" class="text-on-background dark:text-inverse-on-surface hover:text-primary dark:hover:text-primary-fixed-dim break-all font-medium">{{ $supportPhone }}</a>
                    </div>
                </li>
            @endif
            @if ($supportEmail)
                <li class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim text-[22px] shrink-0">mail</span>
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-0.5">Email</p>
                        <a href="mailto:{{ $supportEmail }}" class="text-on-background dark:text-inverse-on-surface hover:text-primary dark:hover:text-primary-fixed-dim break-all font-medium">{{ $supportEmail }}</a>
                    </div>
                </li>
            @endif
            @if ($supportHours)
                <li class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-primary dark:text-primary-fixed-dim text-[22px] shrink-0">schedule</span>
                    <div>
                        <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-0.5">Hours</p>
                        <p class="text-on-background dark:text-inverse-on-surface">{{ $supportHours }}</p>
                    </div>
                </li>
            @endif
            @if (! $supportPhone && ! $supportEmail)
                <li class="text-on-surface-variant dark:text-outline-variant">
                    Support contact details are not configured yet. Please ask your campus IT team for phone or email.
                </li>
            @endif
        </ul>

        <div class="mt-6 pt-4 border-t border-outline-variant/30 dark:border-outline/30 space-y-2">
            <p class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface font-bold">
                When you reach out, include your account phone number:
            </p>
            <p class="font-headline-lg-mobile sm:font-headline-lg text-headline-lg-mobile sm:text-headline-lg text-primary dark:text-primary-fixed-dim font-bold tabular-nums tracking-wide">
                {{ auth()->user()->phone_number }}
            </p>
        </div>
    </div>
</div>
@endsection
