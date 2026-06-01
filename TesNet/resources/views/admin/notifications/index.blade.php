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
                        <input type="hidden" name="is_global" value="0"/>
                        <input type="checkbox" name="is_global" value="1" {{ old('is_global', true) ? 'checked' : '' }}
                            class="mt-1 w-5 h-5 rounded border-outline text-primary focus:ring-primary dark:focus:ring-primary-fixed-dim"/>
                        <span class="font-body-md text-body-md admin-card-strong group-hover:text-primary dark:group-hover:text-primary-fixed-dim transition-colors">Show to Everyone (Global Broadcast)</span>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer group min-h-[44px]">
                        <input type="hidden" name="expire_24_hours" value="0"/>
                        <input type="checkbox" name="expire_24_hours" value="1" {{ old('expire_24_hours', true) ? 'checked' : '' }}
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
                    <button type="button" onclick="document.getElementById('announcement-form').reset();"
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
</div>
@endsection
