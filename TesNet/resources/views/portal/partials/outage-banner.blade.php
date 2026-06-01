@php
    $status = \App\Support\PortalStatus::current();
@endphp

@if (($status['outage_enabled'] ?? false) && ! empty(trim((string) ($status['outage_message'] ?? ''))))
    <div class="mx-4 mt-3 md:mx-6 md:mt-4 rounded-lg border border-error-container bg-error-container/35 dark:bg-error-container/20 px-4 py-3 text-error font-body-md text-sm flex items-start gap-2">
        <span class="material-symbols-outlined fill text-[18px] mt-0.5">warning</span>
        <div class="min-w-0">
            <p class="font-bold">Service notice</p>
            <p class="text-error/90 dark:text-error/90 whitespace-pre-line">{{ trim((string) $status['outage_message']) }}</p>
        </div>
    </div>
@endif

