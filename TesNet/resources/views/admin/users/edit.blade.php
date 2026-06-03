@extends('admin.layouts.hub')

@section('title', 'Reset password — '.$user->phone_number)

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-lg w-full mr-auto">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 font-label-sm text-label-sm text-primary dark:text-primary-fixed-dim hover:underline mb-4">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> All students
    </a>
    <h1 class="font-headline-lg-mobile text-headline-lg-mobile text-on-surface dark:text-inverse-on-surface mb-2">Edit student</h1>
    <p class="font-mono text-body-md admin-card-muted mb-6">{{ $user->phone_number }}</p>
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-card rounded-xl p-6 soft-shadow space-y-4 border border-outline-variant/20">
        @csrf @method('PUT')
        <p class="font-label-sm admin-card-muted rounded-lg border border-outline-variant/20 bg-surface-container-low dark:bg-admin-elevated-high p-3">
            <span class="font-semibold admin-card-strong">One account, one device.</span>
            Each student may use {{ $user->device_limit }} device at a time. Sharing login details is not allowed.
        </p>
        <div class="rounded-lg border border-outline-variant/20 bg-surface-container-low dark:bg-admin-elevated-high p-4">
            <label class="flex items-start gap-3">
                <input type="hidden" name="is_suspended" value="0"/>
                <input type="checkbox" name="is_suspended" value="1" {{ old('is_suspended', $user->is_suspended) ? 'checked' : '' }} class="w-5 h-5 mt-0.5 accent-primary"/>
                <span class="min-w-0">
                    <span class="block font-body-md font-semibold admin-card-strong">Suspend account</span>
                    <span class="block font-label-sm admin-card-muted mt-1">Blocks portal login and sets RADIUS to reject this username.</span>
                </span>
            </label>
        </div>
        <div class="rounded-lg border border-primary/20 bg-primary-container/10 dark:bg-primary-container/10 p-4 mb-4">
            <p class="font-body-md text-body-md admin-card-strong font-semibold mb-1">Reset student password</p>
            <p class="font-label-sm admin-card-muted">Students cannot reset passwords themselves. Set a new password here — it updates portal login and Wi‑Fi (RADIUS).</p>
        </div>
        <div>
            <label class="font-label-sm admin-card-muted block mb-1">New password</label>
            <input type="password" name="password" autocomplete="new-password" class="portal-input min-h-[48px]" placeholder="Leave blank to keep current"/>
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
