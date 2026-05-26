<form method="POST" action="{{ $action ?? route('portal.logout') }}" class="{{ $class ?? '' }}">
    @csrf
    <button type="submit"
        class="{{ $buttonClass ?? 'w-full flex items-center justify-center gap-2 min-h-[48px] px-4 py-3 rounded-lg font-label-sm text-label-sm text-error dark:text-red-400 bg-error-container/20 dark:bg-error-container/10 border border-error/30 dark:border-error/40 hover:bg-error-container/40 dark:hover:bg-error-container/20 transition-colors active:scale-[0.98]' }}">
        <span class="material-symbols-outlined text-[20px]">logout</span>
        <span>{{ $label ?? 'Log out' }}</span>
    </button>
</form>
