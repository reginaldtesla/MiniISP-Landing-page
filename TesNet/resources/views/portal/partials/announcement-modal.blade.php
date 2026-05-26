@php
    $modalNotice = $announcement ?? null;
@endphp
@if ($modalNotice)
<div id="announcement-modal"
    class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="announcement-modal-title"
    data-announcement-id="{{ $modalNotice->id }}">
    <div class="absolute inset-0 bg-on-background/50 dark:bg-black/60 backdrop-blur-sm" data-announcement-dismiss></div>
    <div class="relative w-full max-w-md bg-surface dark:bg-inverse-surface rounded-2xl shadow-[0_8px_32px_rgba(0,0,0,0.2)] dark:shadow-[0_8px_40px_rgba(0,0,0,0.5)] border border-outline-variant/30 dark:border-outline/30 overflow-hidden">
        <div @class([
            'px-5 py-3 flex items-center gap-2 border-b border-outline-variant/20',
            'bg-secondary-container/40 text-secondary dark:bg-secondary-container/20 dark:text-secondary-fixed-dim' => $modalNotice->type === 'success',
            'bg-error-container/50 text-error dark:bg-error-container/30' => $modalNotice->type === 'warning',
            'bg-primary-container/30 text-primary dark:bg-primary-container/20 dark:text-primary-fixed-dim' => $modalNotice->type === 'info',
        ])>
            <span class="material-symbols-outlined fill text-[22px]">campaign</span>
            <span class="font-label-sm text-label-sm uppercase tracking-wide">Network announcement</span>
        </div>
        <div class="p-5 sm:p-6">
            <h2 id="announcement-modal-title" class="font-title-md text-title-md text-on-surface dark:text-inverse-on-surface mb-2">
                {{ $modalNotice->title }}
            </h2>
            <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant whitespace-pre-line">{{ $modalNotice->message }}</p>
            <p class="font-label-sm text-label-sm text-on-surface-variant/80 dark:text-outline-variant/80 mt-3">
                {{ $modalNotice->created_at->format('M j, Y g:i A') }}
            </p>
            <button type="button"
                data-announcement-got-it
                class="mt-6 w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm hover:opacity-90 transition-opacity active:scale-[0.98]">
                Got it
            </button>
        </div>
    </div>
</div>
@endif
