@extends('portal.layouts.guest')

@section('title', 'Login — TesNet')

@section('content')
<div class="bg-surface-container dark:bg-inverse-surface rounded-xl shadow-[0_4px_12px_rgba(37,99,235,0.08)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.3)] p-5 sm:p-8 border border-surface-variant/50 dark:border-outline/30">
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary dark:text-primary-fixed-dim font-bold mb-2">Student Login</h1>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-2">Sign in with your phone number and password.</p>
    <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant mb-6">Your account is for you only. Signing in here signs out other browsers and disconnects other devices using your data.</p>
    <form method="POST" action="{{ url('/portal/login') }}" class="space-y-4">
        @csrf
        <div>
            <label for="phone_number" class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" placeholder="0551234567" required autocomplete="tel"
                class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <div class="flex items-center justify-between gap-2 mb-1">
                <label for="password" class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant">Password</label>
                <a href="{{ route('portal.password.forgot') }}" class="font-label-sm text-label-sm text-primary dark:text-primary-fixed-dim hover:underline">Forgot password?</a>
            </div>
            <input type="password" name="password" id="password" required autocomplete="current-password"
                class="portal-input min-h-[48px]"/>
        </div>
        <label class="flex items-center gap-2 font-body-md text-body-md text-on-surface-variant dark:text-outline-variant min-h-[44px]">
            <input type="checkbox" name="remember" class="rounded border-outline-variant text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim w-5 h-5"/>
            Remember me
        </label>
        <button type="submit" class="w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
            Login
        </button>
    </form>
    <p class="mt-6 text-center font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
        No account? <a href="{{ route('portal.register') }}" class="text-primary dark:text-primary-fixed-dim font-semibold hover:underline">Register</a>
    </p>
</div>
@endsection
