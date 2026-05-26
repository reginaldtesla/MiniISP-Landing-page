@extends('admin.layouts.hub')

@section('title', 'Edit Student')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-lg w-full mr-auto">
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface dark:text-inverse-on-surface mb-6">Edit {{ $user->phone_number }}</h1>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-card rounded-xl p-6 soft-shadow space-y-4 border border-outline-variant/20">
        @csrf @method('PUT')
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">Device Limit</label>
            <input type="number" name="device_limit" min="1" max="10" value="{{ old('device_limit', $user->device_limit) }}" class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">New Password (optional)</label>
            <input type="password" name="password" class="portal-input min-h-[48px]"/>
        </div>
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" class="portal-input min-h-[48px]"/>
        </div>
        <button type="submit" class="w-full min-h-[48px] rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm">Save</button>
    </form>
    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-6" onsubmit="return confirm('Delete user?')">@csrf @method('DELETE')
        <button type="submit" class="text-error font-body-md hover:underline">Delete User</button>
    </form>
</div>
@endsection
