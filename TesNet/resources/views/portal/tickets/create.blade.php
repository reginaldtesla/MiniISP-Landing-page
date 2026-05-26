@extends('portal.layouts.dashboard')

@section('title', 'New Ticket — TesNet')

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface mb-6">New Support Ticket</h1>
    <div class="max-w-lg bg-surface-container dark:bg-inverse-surface rounded-xl p-4 sm:p-6 md:p-8 border border-surface-variant/50 dark:border-outline/30 shadow-sm">
        <form method="POST" action="{{ route('portal.tickets.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Title</label>
                <input type="text" name="title" value="{{ old('title') }}" required maxlength="120" class="portal-input min-h-[48px]"/>
            </div>
            <div>
                <label class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant block mb-1">Description</label>
                <textarea name="description" rows="5" required class="portal-input min-h-[120px] resize-y">{{ old('description') }}</textarea>
            </div>
            <button type="submit" class="w-full min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg font-label-sm text-label-sm hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
                Submit Ticket
            </button>
        </form>
    </div>
</div>
@endsection
