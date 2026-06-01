@extends('portal.layouts.guest')

@section('title', 'Forgot Password — TesNet')

@section('content')
<div class="bg-surface-container dark:bg-inverse-surface rounded-xl shadow-[0_4px_12px_rgba(37,99,235,0.08)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.3)] p-5 sm:p-8 border border-surface-variant/50 dark:border-outline/30">
    <div class="flex items-center gap-3 mb-4">
        <span class="material-symbols-outlined text-[28px] text-primary dark:text-primary-fixed-dim">admin_panel_settings</span>
        <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary dark:text-primary-fixed-dim font-bold">Password reset</h1>
    </div>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-4">
        Student passwords cannot be reset online. Only a TesNet administrator can set a new password for your account.
    </p>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-6">
        Contact support with the phone number on your account. An admin will verify you and update your password in the admin panel.
    </p>

  <div class="rounded-lg border border-outline-variant/30 dark:border-outline/30 bg-surface-container-low dark:bg-inverse-surface/50 p-4 space-y-2 font-body-md text-body-md">
        @if (config('portal.support.phone'))
            <p class="flex items-center gap-2 text-on-surface dark:text-inverse-on-surface">
                <span class="material-symbols-outlined text-[20px] text-primary dark:text-primary-fixed-dim">call</span>
                <a href="tel:{{ preg_replace('/\s+/', '', config('portal.support.phone')) }}" class="text-primary dark:text-primary-fixed-dim font-semibold hover:underline">{{ config('portal.support.phone') }}</a>
            </p>
        @endif
        @if (config('portal.support.email'))
            <p class="flex items-center gap-2 text-on-surface dark:text-inverse-on-surface">
                <span class="material-symbols-outlined text-[20px] text-primary dark:text-primary-fixed-dim">mail</span>
                <a href="mailto:{{ config('portal.support.email') }}" class="text-primary dark:text-primary-fixed-dim font-semibold hover:underline break-all">{{ config('portal.support.email') }}</a>
            </p>
        @endif
        @if (config('portal.support.hours'))
            <p class="flex items-start gap-2 text-on-surface-variant dark:text-outline-variant">
                <span class="material-symbols-outlined text-[20px] shrink-0">schedule</span>
                <span>{{ config('portal.support.hours') }}</span>
            </p>
        @endif
        @if (! config('portal.support.phone') && ! config('portal.support.email'))
            <p class="text-on-surface-variant dark:text-outline-variant">Ask your network administrator or visit the TesNet office.</p>
        @endif
    </div>

    <a href="{{ route('portal.login') }}"
        class="mt-6 w-full min-h-[48px] inline-flex items-center justify-center bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm hover:bg-primary/90 transition-colors active:scale-[0.98]">
        Back to login
    </a>
</div>
@endsection
