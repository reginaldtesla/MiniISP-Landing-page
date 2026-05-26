@if (session('status') || $errors->any())
<div class="mb-4 w-full">
    @if (session('status'))
        <div class="mb-3 rounded-lg border border-secondary-container dark:border-secondary/30 bg-secondary-container/30 dark:bg-secondary-container/10 px-3 py-2.5 sm:px-4 sm:py-3 text-secondary dark:text-secondary-fixed-dim font-body-md text-body-md flex items-start gap-2 text-sm sm:text-base">
            <span class="material-symbols-outlined fill text-[20px] shrink-0">check_circle</span>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-3 rounded-lg border border-error-container dark:border-error/40 bg-error-container/50 dark:bg-error-container/20 px-3 py-2.5 sm:px-4 sm:py-3 text-error font-body-md text-body-md text-sm sm:text-base">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endif
