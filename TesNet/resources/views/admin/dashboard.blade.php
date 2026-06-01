@extends('admin.layouts.hub')

@section('title', 'Dashboard — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface mb-6">Dashboard</h1>
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 mb-8">
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Students</p>
            <p class="text-2xl sm:text-3xl font-bold admin-card-strong mt-1">{{ $totalUsers }}</p>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 mt-2 font-label-sm text-label-sm text-primary dark:text-primary-fixed-dim hover:underline">
                Manage students <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
            </a>
        </div>
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Live Sessions</p>
            <p class="text-2xl sm:text-3xl font-bold text-secondary dark:text-secondary-fixed-dim mt-1">{{ $activeSessions }}</p>
        </div>
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Package Revenue</p>
            <p class="text-xl sm:text-2xl font-bold admin-card-strong mt-1">GH¢{{ number_format($packageRevenue, 0) }}</p>
        </div>
    </div>
    <div class="bg-surface-container-low dark:bg-inverse-surface/50 rounded-xl p-4 mb-6 border border-outline-variant/20 flex flex-wrap gap-2">
        <a href="{{ route('admin.notifications.index') }}" class="min-h-[44px] px-4 py-2 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">campaign</span> Announcements
        </a>
        <a href="{{ route('admin.users.index') }}" class="min-h-[44px] px-4 py-2 rounded-lg border-2 border-primary text-primary dark:border-primary-fixed-dim dark:text-primary-fixed-dim font-label-sm text-label-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">lock_reset</span> Reset passwords
        </a>
        <a href="{{ route('admin.sessions.index') }}" class="min-h-[44px] px-4 py-2 rounded-lg border-2 border-secondary text-secondary dark:border-secondary-fixed-dim dark:text-secondary-fixed-dim font-label-sm text-label-sm">Sessions</a>
        <a href="{{ route('admin.system-health.index') }}" class="min-h-[44px] px-4 py-2 rounded-lg border border-outline-variant/30 admin-card-muted font-label-sm text-label-sm">System health</a>
        <a href="{{ route('admin.manual-payments.index') }}" class="min-h-[44px] px-4 py-2 rounded-lg border border-outline-variant/30 admin-card-muted font-label-sm text-label-sm">Manual pay</a>
    </div>
    <h2 class="font-title-md text-title-md text-on-surface dark:text-inverse-on-surface mb-3">Recent Paystack Payments</h2>
    <div class="overflow-x-auto rounded-xl border border-outline-variant/30 admin-card soft-shadow">
        <table class="w-full text-sm font-body-md min-w-[500px]">
            <thead class="bg-surface-container-high dark:bg-admin-elevated-high text-on-surface-variant dark:text-outline-variant">
                <tr><th class="p-3 text-left">User</th><th class="p-3 text-left">Type</th><th class="p-3 text-left">Amount</th><th class="p-3 text-left">Paid</th></tr>
            </thead>
            <tbody class="admin-card-strong">
                @forelse ($recentTransactions as $tx)
                    <tr class="border-t border-outline-variant/20">
                        <td class="p-3">{{ $tx->user?->phone_number }}</td>
                        <td class="p-3">{{ $tx->type }}</td>
                        <td class="p-3">GH¢{{ number_format($tx->amount, 2) }}</td>
                        <td class="p-3 admin-card-muted">{{ $tx->paid_at?->format('M j, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center admin-card-muted">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
