@extends('admin.layouts.hub')

@section('title', 'Announcements — TESNET Admin')

@section('content')
<div class="flex-1 flex flex-col xl:flex-row min-h-0 overflow-hidden w-full">
    {{-- Recent Messages --}}
    <aside class="w-full lg:w-80 xl:w-96 bg-surface-bright dark:bg-inverse-surface border-b lg:border-b-0 lg:border-r border-outline-variant/30 flex flex-col shrink-0 max-h-[40vh] lg:max-h-none lg:h-full">
        <div class="p-4 sm:p-6 border-b border-outline-variant/20 shrink-0">
            <h2 class="font-headline-lg-mobile text-headline-lg-mobile admin-card-strong mb-1">Recent Messages</h2>
            <p class="font-body-md text-body-md admin-card-muted text-sm">Past broadcasts to students</p>
        </div>
        <div class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-3">
            @forelse ($recentMessages as $msg)
                <div class="admin-card p-4 rounded-xl soft-shadow border border-transparent hover:border-primary/20 dark:hover:border-primary-fixed-dim/30 transition-colors group relative">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        @if ($msg->isActive())
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-secondary-container/30 text-secondary dark:text-secondary-fixed-dim font-label-sm text-[10px] shrink-0">
                                <span class="material-symbols-outlined text-[14px]">done_all</span> Delivered
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-outline-variant/30 admin-card-muted font-label-sm text-[10px] shrink-0">
                                <span class="material-symbols-outlined text-[14px]">history</span> Expired
                            </span>
                        @endif
                        <span class="font-label-sm text-label-sm text-outline dark:text-outline-variant text-right shrink-0">{{ $msg->formattedSentAt() }}</span>
                    </div>
                    <h3 class="font-body-md text-body-md admin-card-strong font-medium mb-1 pr-8">{{ $msg->title }}</h3>
                    <p class="font-body-md text-body-md admin-card-muted text-sm line-clamp-2">{{ $msg->message }}</p>
                    <form method="POST" action="{{ route('admin.notifications.destroy', $msg) }}" class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity" onsubmit="return confirm('Delete this announcement?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1 rounded text-outline hover:text-error" title="Delete">
                            <span class="material-symbols-outlined text-[18px]">delete</span>
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-center font-body-md text-body-md admin-card-muted py-8 px-4">No announcements sent yet.</p>
            @endforelse
        </div>
    </aside>

    {{-- Compose --}}
    <section class="flex-1 flex flex-col min-h-0 overflow-y-auto bg-surface dark:bg-inverse-surface">
        <div class="max-w-3xl w-full mr-auto p-4 sm:p-6 md:p-8 lg:p-10">
            <div class="mb-6 sm:mb-8">
                <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg admin-card-strong mb-2">Write a New Message</h1>
                <p class="font-body-md text-body-md admin-card-muted">Send a friendly update or alert to student devices.</p>
            </div>

            <form method="POST" action="{{ route('admin.notifications.store') }}" id="announcement-form"
                class="admin-card rounded-xl p-4 sm:p-6 md:p-8 soft-shadow border border-outline-variant/20 dark:border-outline-variant/10">
                @csrf
                <div class="mb-6">
                    <label for="message-title" class="block font-body-md font-medium text-body-md admin-card-strong mb-2">Announcement Title</label>
                    <input type="text" name="title" id="message-title" value="{{ old('title') }}" required maxlength="120"
                        class="portal-input min-h-[48px]"
                        placeholder="e.g., Quick Maintenance Update"/>
                </div>
                <div class="mb-6">
                    <label for="message-body" class="block font-body-md font-medium text-body-md admin-card-strong mb-2">Message Details</label>
                    <textarea name="message" id="message-body" rows="5" required maxlength="5000"
                        class="portal-input min-h-[120px] resize-y"
                        placeholder="What do students need to know? Keep it friendly and clear...">{{ old('message') }}</textarea>
                </div>
                <div class="space-y-4 mb-6 sm:mb-8 p-4 bg-surface-container-low dark:bg-inverse-surface/50 border border-outline-variant/20 rounded-lg">
                    <label class="flex items-start gap-3 cursor-pointer group min-h-[44px]">
                        <input type="checkbox" name="is_global" value="1" checked
                            class="mt-1 w-5 h-5 rounded border-outline text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim"/>
                        <span class="font-body-md text-body-md admin-card-strong group-hover:text-primary dark:group-hover:text-primary-fixed-dim transition-colors">Show to Everyone (Global Broadcast)</span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer group min-h-[44px]">
                        <input type="checkbox" name="expire_24_hours" value="1" checked
                            class="mt-1 w-5 h-5 rounded border-outline text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim"/>
                        <span class="font-body-md text-body-md admin-card-strong group-hover:text-primary dark:group-hover:text-primary-fixed-dim transition-colors">Show for 24 hours only</span>
                    </label>
                    <div class="hidden">
                        <select name="type">
                            <option value="info" selected>info</option>
                            <option value="warning">warning</option>
                            <option value="success">success</option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-4">
                    <button type="button" onclick="document.getElementById('announcement-form').reset(); document.getElementById('message-title').dispatchEvent(new Event('input'));"
                        class="min-h-[48px] px-6 py-3 rounded-xl bg-transparent border-2 border-secondary dark:border-secondary-fixed-dim text-secondary dark:text-secondary-fixed-dim font-body-md font-medium hover:bg-secondary/5 transition-colors">
                        Clear
                    </button>
                    <button type="submit"
                        class="min-h-[56px] px-8 py-3 rounded-xl bg-primary dark:bg-primary-fixed-dim text-on-primary dark:text-on-primary-fixed font-body-md font-medium hover:brightness-95 transition-all soft-shadow flex items-center justify-center gap-2 active:scale-[0.98]">
                        <span class="material-symbols-outlined">send</span> Send Now
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Live Preview --}}
    <aside class="hidden xl:flex w-80 2xl:w-96 bg-surface-container-low dark:bg-inverse-surface border-l border-outline-variant/30 flex-col items-center py-8 px-4 shrink-0 overflow-y-auto">
        <div class="mb-6 text-center">
            <h3 class="font-title-md text-title-md admin-card-strong mb-1">Live Preview</h3>
            <p class="font-body-md text-body-md admin-card-muted text-sm">How it appears on a student's phone</p>
        </div>
        <div class="w-[280px] 2xl:w-[300px] h-[580px] 2xl:h-[620px] admin-card rounded-[36px] border-[6px] border-surface-variant dark:border-outline-variant/40 shadow-xl relative overflow-hidden flex flex-col shrink-0">
            <div class="h-6 w-full flex justify-between items-center px-4 pt-2 text-[10px] admin-card-muted bg-surface dark:bg-inverse-surface">
                <span>9:41</span>
                <div class="flex gap-0.5">
                    <span class="material-symbols-outlined text-[12px]">signal_cellular_4_bar</span>
                    <span class="material-symbols-outlined text-[12px]">wifi</span>
                    <span class="material-symbols-outlined text-[12px]">battery_full</span>
                </div>
            </div>
            <div class="flex-1 bg-surface dark:bg-inverse-surface p-3 relative pt-8">
                <h2 class="font-title-md text-title-md font-bold text-primary dark:text-primary-fixed-dim mb-4 text-sm">Home</h2>
                <div class="bg-primary/5 dark:bg-primary-fixed-dim/10 rounded-xl p-3 mb-3 h-24 border border-primary/10"></div>
                <div class="bg-surface-container-low dark:bg-admin-elevated-high/50 rounded-xl p-3 h-16 mb-3"></div>
                <div class="bg-surface-container-low dark:bg-admin-elevated-high/50 rounded-xl p-3 h-16"></div>
                <div class="absolute bottom-4 left-3 right-3 admin-card rounded-2xl p-4 shadow-lg border border-primary/10 dark:border-outline-variant/20">
                    <div class="flex justify-between items-start mb-2">
                        <div class="w-9 h-9 rounded-full bg-primary-container/30 dark:bg-primary-container/40 flex items-center justify-center text-primary dark:text-primary-fixed-dim">
                            <span class="material-symbols-outlined fill text-[20px]">campaign</span>
                        </div>
                        <span class="material-symbols-outlined text-outline text-[18px]">close</span>
                    </div>
                    <h4 id="preview-title" class="font-body-md text-body-md font-medium admin-card-strong mb-1 line-clamp-2">Quick Maintenance Update</h4>
                    <p id="preview-body" class="font-body-md text-body-md admin-card-muted text-xs line-clamp-3">What do students need to know? Keep it friendly and clear...</p>
                    <button type="button" class="mt-3 w-full py-2 bg-primary/10 dark:bg-primary-fixed-dim/20 text-primary dark:text-primary-fixed-dim font-body-md text-sm font-medium rounded-lg">Got it</button>
                </div>
            </div>
            <div class="h-1 w-1/3 bg-outline rounded-full absolute bottom-2 left-1/2 -translate-x-1/2"></div>
        </div>
    </aside>
</div>

{{-- Mobile inline preview --}}
<div class="xl:hidden px-4 pb-4 bg-surface-container-low dark:bg-inverse-surface border-t border-outline-variant/30">
    <p class="font-label-sm text-label-sm admin-card-muted text-center py-3">Mobile preview</p>
    <div class="max-w-sm mx-auto admin-card rounded-2xl p-4 soft-shadow border border-primary/10">
        <div class="flex gap-3 mb-2">
            <div class="w-9 h-9 rounded-full bg-primary-container/30 flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined fill text-[18px]">campaign</span>
            </div>
            <div class="min-w-0 flex-1">
                <h4 id="preview-title-mobile" class="font-body-md font-medium admin-card-strong truncate">Quick Maintenance Update</h4>
                <p id="preview-body-mobile" class="font-body-md text-sm admin-card-muted line-clamp-2">What do students need to know?</p>
            </div>
        </div>
        <button type="button" class="w-full py-2 bg-primary/10 text-primary text-sm font-medium rounded-lg">Got it</button>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const titleInput = document.getElementById('message-title');
    const bodyInput = document.getElementById('message-body');
    const previews = [
        { title: document.getElementById('preview-title'), body: document.getElementById('preview-body') },
        { title: document.getElementById('preview-title-mobile'), body: document.getElementById('preview-body-mobile') },
    ].filter(p => p.title && p.body);

    function sync() {
        const t = titleInput?.value?.trim() || 'Announcement Title';
        const b = bodyInput?.value?.trim() || 'What do students need to know? Keep it friendly and clear...';
        previews.forEach(p => {
            p.title.textContent = t;
            p.body.textContent = b;
        });
    }
    titleInput?.addEventListener('input', sync);
    bodyInput?.addEventListener('input', sync);
    sync();
})();
</script>
@endpush
