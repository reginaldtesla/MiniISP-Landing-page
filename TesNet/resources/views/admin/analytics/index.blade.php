@extends('admin.layouts.hub')

@section('title', 'Analytics — TESNET Admin')

@php
    $formatBytes = function (int $bytes): string {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2).' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2).' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2).' KB';
        return $bytes.' B';
    };
@endphp

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface">Analytics</h1>
            <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">Money + troubleshooting overview (last 14 days).</p>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-8">
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Total revenue</p>
            <p class="text-xl sm:text-2xl font-bold admin-card-strong mt-1">GH¢{{ number_format($totalRevenue, 0) }}</p>
        </div>
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Live sessions</p>
            <p class="text-2xl sm:text-3xl font-bold text-secondary dark:text-secondary-fixed-dim mt-1">{{ $activeSessions }}</p>
        </div>
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Usage (last 24h)</p>
            <p class="text-xl sm:text-2xl font-bold admin-card-strong mt-1">{{ $formatBytes($last24hUsageBytes) }}</p>
        </div>
        <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
            <p class="font-label-sm text-label-sm admin-card-muted">Days tracked</p>
            <p class="text-xl sm:text-2xl font-bold admin-card-strong mt-1">14</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 mb-8">
        <div class="admin-card rounded-xl soft-shadow border border-outline-variant/20 overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20">
                <h2 class="font-title-md text-title-md admin-card-strong">Revenue by channel</h2>
                <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">Paystack vs manual approvals (all time).</p>
            </div>
            <table class="w-full text-sm font-body-md">
                <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                    <tr><th class="p-3 text-left">Channel</th><th class="p-3 text-left">Count</th><th class="p-3 text-left">Revenue</th></tr>
                </thead>
                <tbody class="admin-card-strong">
                    @forelse ($revenueByChannel as $row)
                        <tr class="border-t border-outline-variant/20">
                            <td class="p-3">{{ $row->channel }}</td>
                            <td class="p-3">{{ $row->count }}</td>
                            <td class="p-3 font-semibold">GH¢{{ number_format((float) $row->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-6 text-center admin-card-muted">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="admin-card rounded-xl soft-shadow border border-outline-variant/20 overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20">
                <h2 class="font-title-md text-title-md admin-card-strong">Revenue by type</h2>
                <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">Package vs custom data (all time).</p>
            </div>
            <table class="w-full text-sm font-body-md">
                <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                    <tr><th class="p-3 text-left">Type</th><th class="p-3 text-left">Count</th><th class="p-3 text-left">Revenue</th></tr>
                </thead>
                <tbody class="admin-card-strong">
                    @forelse ($revenueByType as $row)
                        <tr class="border-t border-outline-variant/20">
                            <td class="p-3">{{ $row->type }}</td>
                            <td class="p-3">{{ $row->count }}</td>
                            <td class="p-3 font-semibold">GH¢{{ number_format((float) $row->revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="p-6 text-center admin-card-muted">No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="admin-card rounded-xl soft-shadow border border-outline-variant/20 overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20">
                <h2 class="font-title-md text-title-md admin-card-strong">Revenue by day</h2>
                <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">Successful payments grouped by paid date.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm font-body-md min-w-[520px]">
                    <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                        <tr><th class="p-3 text-left">Day</th><th class="p-3 text-left">Payments</th><th class="p-3 text-left">Revenue</th></tr>
                    </thead>
                    <tbody class="admin-card-strong">
                        @forelse ($revenueByDay as $row)
                            <tr class="border-t border-outline-variant/20">
                                <td class="p-3">{{ \Illuminate\Support\Carbon::parse($row->day)->format('M j') }}</td>
                                <td class="p-3">{{ $row->count }}</td>
                                <td class="p-3 font-semibold">GH¢{{ number_format((float) $row->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-6 text-center admin-card-muted">No data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-card rounded-xl soft-shadow border border-outline-variant/20 overflow-hidden">
            <div class="p-4 border-b border-outline-variant/20">
                <h2 class="font-title-md text-title-md admin-card-strong">Top packages (active)</h2>
                <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">Most common active purchases right now.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm font-body-md min-w-[520px]">
                    <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                        <tr><th class="p-3 text-left">Package</th><th class="p-3 text-left">Slug</th><th class="p-3 text-left">Count</th></tr>
                    </thead>
                    <tbody class="admin-card-strong">
                        @forelse ($topPackages as $row)
                            <tr class="border-t border-outline-variant/20">
                                <td class="p-3">{{ $row->package_name }}</td>
                                <td class="p-3 font-mono">{{ $row->package_slug }}</td>
                                <td class="p-3 font-semibold">{{ $row->purchases }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="p-6 text-center admin-card-muted">No data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

