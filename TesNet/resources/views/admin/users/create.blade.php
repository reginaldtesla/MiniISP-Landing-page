@extends('admin.layouts.hub')

@section('title', 'Add Student')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-lg w-full mr-auto">
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface dark:text-inverse-on-surface mb-2">Add student</h1>
    <p class="font-body-md admin-card-muted mb-6">Creates a portal account and syncs credentials to FreeRADIUS.</p>
    <form method="POST" action="{{ route('admin.users.store') }}" class="admin-card rounded-xl p-6 soft-shadow space-y-4 border border-outline-variant/20">
        @csrf
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">Phone number</label>
            <input type="tel" name="phone_number" value="{{ old('phone_number') }}" placeholder="0551234567" required autocomplete="tel" class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">Password</label>
            <input type="password" name="password" required minlength="6" class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">Confirm password</label>
            <input type="password" name="password_confirmation" required minlength="6" class="portal-input min-h-[48px]"/>
        </div>
        <p class="font-label-sm admin-card-muted">One phone number = one account. Device limit is fixed at 1 (no sharing).</p>
        <button type="submit" class="w-full min-h-[48px] rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm">Create student</button>
    </form>
    <p class="mt-4">
        <a href="{{ route('admin.users.index') }}" class="font-body-md text-primary dark:text-primary-fixed-dim hover:underline">← All students</a>
    </p>
</div>
@endsection
