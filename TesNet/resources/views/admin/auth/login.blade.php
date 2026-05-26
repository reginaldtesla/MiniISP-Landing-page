@extends('admin.layouts.guest')

@section('title', 'Admin Login — TESNET')

@section('content')
<div class="admin-card rounded-xl shadow-[0_4px_16px_rgba(0,74,198,0.12)] dark:shadow-[0_4px_20px_rgba(0,0,0,0.35)] p-5 sm:p-8 border border-primary/20 dark:border-outline/30">
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary dark:text-primary-fixed-dim font-bold mb-2">Administrator Login</h1>
    <p class="font-body-md text-body-md admin-card-muted mb-6">Manage packages, users, sessions, and announcements. Students sign in separately to buy data and connect to Wi‑Fi.</p>
    @if (! empty($studentSignedIn))
        <div class="mb-4 rounded-lg border border-tertiary-fixed/40 bg-tertiary-fixed/30 dark:bg-tertiary-fixed-dim/10 px-4 py-3 font-body-md text-sm admin-card-muted">
            You are signed in as a <strong class="admin-card-strong">student</strong> in another tab or session.
            <a href="{{ route('portal.dashboard') }}" class="text-primary dark:text-primary-fixed-dim font-semibold hover:underline">Go to student portal</a>
            or log out there before using admin sign-in.
        </div>
    @endif
    <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="phone_number" class="font-label-sm text-label-sm admin-card-muted block mb-1">Admin Phone</label>
            <input type="tel" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" placeholder="{{ config('admin.phone') }}" required autocomplete="tel"
                class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label for="password" class="font-label-sm text-label-sm admin-card-muted block mb-1">Password</label>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                class="portal-input min-h-[48px]"/>
        </div>
        <label class="flex items-center gap-2 font-body-md text-body-md admin-card-muted min-h-[44px]">
            <input type="checkbox" name="remember" class="rounded border-outline-variant text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim w-5 h-5"/>
            Remember me
        </label>
        <button type="submit" class="w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
            Sign in to Admin
        </button>
    </form>
</div>
@endsection
