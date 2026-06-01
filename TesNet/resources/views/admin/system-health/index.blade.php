@extends('admin.layouts.hub')

@section('title', 'System Health — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface mb-2">System health</h1>
    <p class="font-body-md text-body-md admin-card-muted text-sm mb-6">Production checklist for MikroTik, RADIUS, Paystack, and the portal server.</p>

    <div class="grid gap-3 mb-8">
        @foreach ($checks as $check)
            @php
                $badge = match ($check['status']) {
                    'ok' => 'bg-secondary-container/40 text-secondary dark:text-secondary-fixed-dim',
                    'warn' => 'bg-tertiary-fixed/40 text-tertiary dark:text-tertiary-fixed-dim',
                    default => 'bg-error-container/40 text-error',
                };
            @endphp
            <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20 flex flex-wrap gap-3 items-start justify-between">
                <div class="min-w-0 flex-1">
                    <p class="font-title-md text-title-md admin-card-strong">{{ $check['label'] }}</p>
                    <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">{{ $check['detail'] }}</p>
                </div>
                <span class="shrink-0 px-3 py-1 rounded-full font-label-sm text-label-sm uppercase {{ $badge }}">{{ $check['status'] }}</span>
            </div>
        @endforeach
    </div>

    <h2 class="font-title-md text-title-md admin-card-strong mb-3">MikroTik walled garden (anti-leak)</h2>
    <div class="space-y-4 mb-8">
        @foreach ($walledGardenNotes as $section)
            <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
                <p class="font-body-md text-body-md font-semibold admin-card-strong mb-2">{{ $section['title'] }}</p>
                <ul class="list-disc list-inside font-body-md text-body-md admin-card-muted text-sm space-y-1">
                    @foreach ($section['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
        <p class="font-body-md text-body-md admin-card-strong mb-2">CLI checks</p>
        <pre class="text-xs font-mono admin-card-muted overflow-x-auto p-3 rounded-lg bg-surface-container-low dark:bg-admin-elevated-high">php artisan tesnet:monitor
php artisan tesnet:backup-database
sudo mysql tesnet -e "SELECT username, acctstarttime, acctupdatetime FROM radacct ORDER BY radacctid DESC LIMIT 5;"</pre>
        <p class="font-label-sm text-label-sm admin-card-muted mt-3">See <code class="text-primary dark:text-primary-fixed-dim">docs/PRODUCTION_CHECKLIST.md</code> for full go-live steps.</p>
    </div>
</div>
@endsection
