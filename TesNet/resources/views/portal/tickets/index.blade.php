@extends('portal.layouts.dashboard')

@section('title', 'Support — TesNet')

@php $card = 'bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30'; @endphp

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6 sm:mb-8">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface">Support</h1>
            <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">Your help tickets</p>
        </div>
        <a href="{{ route('portal.tickets.create') }}"
           class="w-full sm:w-auto min-h-[48px] bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed rounded-lg px-6 font-label-sm text-label-sm flex items-center justify-center gap-2 hover:bg-primary/90 dark:hover:bg-primary-fixed-dim/90 transition-colors active:scale-[0.98]">
            <span class="material-symbols-outlined">add</span> New Ticket
        </a>
    </div>
    <div class="space-y-3">
        @forelse ($tickets as $ticket)
            <div class="{{ $card }} p-4 sm:p-5">
                <div class="flex flex-col xs:flex-row xs:justify-between xs:items-start gap-2">
                    <p class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface break-words">{{ $ticket->title }}</p>
                    <span @class([
                        'font-label-sm text-label-sm px-2 py-1 rounded-full shrink-0 w-fit',
                        'bg-secondary-container/40 dark:bg-secondary-container/20 text-secondary dark:text-secondary-fixed-dim' => $ticket->status === 'open',
                        'bg-primary-fixed dark:bg-primary-container/30 text-primary dark:text-primary-fixed-dim' => $ticket->status === 'in-progress',
                        'bg-surface-variant dark:bg-outline/30 text-on-surface-variant dark:text-outline-variant' => $ticket->status === 'closed',
                    ])>{{ $ticket->status }}</span>
                </div>
                <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mt-2 break-words">{{ Str::limit($ticket->description, 200) }}</p>
                <p class="font-label-sm text-label-sm text-outline dark:text-outline-variant mt-2">{{ $ticket->created_at->diffForHumans() }}</p>
            </div>
        @empty
            <div class="bg-surface-container-low dark:bg-inverse-surface rounded-xl p-8 text-center border border-surface-variant/30 dark:border-outline/30">
                <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant">No tickets yet.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
</div>
@endsection
