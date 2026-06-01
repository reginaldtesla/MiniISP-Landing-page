@extends('admin.layouts.hub')

@section('title', 'Portal Settings — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <div class="flex items-center justify-between gap-3 mb-6">
        <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface">Portal Settings</h1>
    </div>

    <div class="admin-card rounded-xl p-5 soft-shadow border border-outline-variant/20">
        <form method="POST" action="{{ route('admin.portal-settings.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="font-title-md text-title-md admin-card-strong">Outage / maintenance banner</p>
                    <p class="font-body-md text-body-md admin-card-muted mt-1">Show a notice on student pages (and optionally block purchases/connect).</p>
                </div>
                <label class="flex items-center gap-2 shrink-0">
                    <input type="hidden" name="outage_enabled" value="0"/>
                    <input type="checkbox" name="outage_enabled" value="1" {{ old('outage_enabled', $settings->outage_enabled) ? 'checked' : '' }} class="w-5 h-5 accent-primary"/>
                    <span class="font-body-md text-body-md admin-card-strong">Enabled</span>
                </label>
            </div>

            <div>
                <label class="block font-label-sm text-label-sm admin-card-muted mb-2">Banner message</label>
                <textarea name="outage_message" rows="4" class="w-full rounded-lg bg-surface dark:bg-admin-elevated-high border border-outline-variant/30 dark:border-outline/30 px-3 py-2 font-body-md text-body-md admin-card-strong" placeholder="Example: Network maintenance today 2PM–4PM. Please be patient.">{{ old('outage_message', $settings->outage_message) }}</textarea>
                <p class="font-label-sm text-label-sm admin-card-muted mt-2">Tip: Keep it short. New lines are supported.</p>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 rounded-lg border border-outline-variant/20 px-4 py-3 bg-surface-container-low dark:bg-admin-elevated-high">
                    <input type="hidden" name="block_purchases" value="0"/>
                    <input type="checkbox" name="block_purchases" value="1" {{ old('block_purchases', $settings->block_purchases) ? 'checked' : '' }} class="w-5 h-5 mt-0.5 accent-primary"/>
                    <span class="min-w-0">
                        <span class="block font-body-md text-body-md font-semibold admin-card-strong">Block purchases</span>
                        <span class="block font-label-sm text-label-sm admin-card-muted mt-0.5">Students can view packages but can’t start checkout.</span>
                    </span>
                </label>

                <label class="flex items-start gap-3 rounded-lg border border-outline-variant/20 px-4 py-3 bg-surface-container-low dark:bg-admin-elevated-high">
                    <input type="hidden" name="block_connect" value="0"/>
                    <input type="checkbox" name="block_connect" value="1" {{ old('block_connect', $settings->block_connect) ? 'checked' : '' }} class="w-5 h-5 mt-0.5 accent-primary"/>
                    <span class="min-w-0">
                        <span class="block font-body-md text-body-md font-semibold admin-card-strong">Block connect button</span>
                        <span class="block font-label-sm text-label-sm admin-card-muted mt-0.5">Dashboard “Connect to Internet” will be disabled.</span>
                    </span>
                </label>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <button type="submit" class="min-h-[44px] px-5 py-2 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm">
                    Save settings
                </button>
                <a href="{{ route('admin.dashboard') }}" class="min-h-[44px] px-5 py-2 rounded-lg border border-outline-variant/30 admin-card-muted font-label-sm text-label-sm flex items-center">
                    Back
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

