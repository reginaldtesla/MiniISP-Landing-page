@extends('portal.layouts.guest')

@section('title', 'Register — TesNet')

@section('content')
<div class="bg-surface-container dark:bg-inverse-surface rounded-xl shadow-[0_4px_12px_rgba(37,99,235,0.08)] dark:shadow-[0_4px_16px_rgba(0,0,0,0.3)] p-5 sm:p-8 border border-surface-variant/50 dark:border-outline/30">
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-primary dark:text-primary-fixed-dim font-bold mb-2">Create Account</h1>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-6">Register with your Ghana mobile number.</p>
    <form method="POST" action="{{ url('/portal/register') }}" class="space-y-4">
        @csrf
        <div>
            <label for="phone_number" class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Phone Number</label>
            <input type="tel" name="phone_number" id="phone_number" value="{{ old('phone_number') }}" placeholder="0551234567" required autocomplete="tel"
                class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label for="password" class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Password</label>
            <input type="password" name="password" id="password" required minlength="6" autocomplete="new-password"
                class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label for="password_confirmation" class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required autocomplete="new-password"
                class="portal-input min-h-[48px]"/>
        </div>
        <button type="submit" class="w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
            Register
        </button>
    </form>
    <p class="mt-6 text-center font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">
        Already registered? <a href="{{ route('portal.login') }}" class="text-primary dark:text-primary-fixed-dim font-semibold hover:underline">Login</a>
    </p>
</div>
@endsection
