@extends('admin.layouts.hub')

@section('title', 'Settings — Students')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-2">
        <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface">Students</h1>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center min-h-[44px] px-4 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm">Add student</a>
    </div>
    <p class="font-body-md text-body-md admin-card-muted mb-6">To reset a password: click <strong class="admin-card-strong">Edit</strong> on a student, enter a new password, and save.</p>
    <div class="md:hidden space-y-3">
        @foreach ($users as $u)
            <div class="admin-card rounded-xl p-4 soft-shadow flex justify-between items-center gap-2">
                <div class="min-w-0">
                    <p class="font-mono text-sm text-on-surface dark:text-inverse-on-surface">{{ $u->phone_number }}</p>
                    <p class="font-label-sm admin-card-muted">{{ $u->device_limit }} device{{ $u->device_limit === 1 ? '' : 's' }}</p>
                </div>
                <a href="{{ route('admin.users.edit', $u) }}" class="shrink-0 text-primary dark:text-primary-fixed-dim font-label-sm">Reset password</a>
            </div>
        @endforeach
    </div>
    <div class="hidden md:block overflow-x-auto rounded-xl border border-outline-variant/30 admin-card soft-shadow">
        <table class="w-full text-sm font-body-md">
            <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                <tr><th class="p-3 text-left">Phone</th><th class="p-3 text-left">Devices</th><th class="p-3 text-left">Joined</th><th class="p-3"></th></tr>
            </thead>
            <tbody class="text-on-surface dark:text-inverse-on-surface">
                @foreach ($users as $u)
                    <tr class="border-t border-outline-variant/20">
                        <td class="p-3 font-mono">{{ $u->phone_number }}</td>
                        <td class="p-3">{{ $u->device_limit }}</td>
                        <td class="p-3 admin-card-muted">{{ $u->created_at->format('M j, Y') }}</td>
                        <td class="p-3"><a href="{{ route('admin.users.edit', $u) }}" class="text-primary dark:text-primary-fixed-dim hover:underline">Edit / reset password</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
    <div class="mt-8 flex flex-wrap gap-3">
        <a href="{{ route('admin.sessions.index') }}" class="font-body-md text-primary dark:text-primary-fixed-dim hover:underline">Live Sessions</a>
    </div>
</div>
@endsection
