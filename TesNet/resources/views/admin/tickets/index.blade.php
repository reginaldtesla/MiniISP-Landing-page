@extends('admin.layouts.hub')

@section('title', 'Tickets — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface mb-4">Support Tickets</h1>
    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select name="status" class="portal-input w-auto min-w-[140px] min-h-[44px]">
            <option value="">All statuses</option>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected($filterStatus === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="min-h-[44px] px-4 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm">Filter</button>
    </form>
    <div class="space-y-3 md:hidden">
        @foreach ($tickets as $ticket)
            <div class="admin-card rounded-xl p-4 soft-shadow">
                <p class="font-medium text-on-surface dark:text-inverse-on-surface">{{ $ticket->title }}</p>
                <p class="text-sm admin-card-muted font-mono">{{ $ticket->user?->phone_number }}</p>
                <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" class="mt-2">@csrf @method('PATCH')
                    <select name="status" onchange="this.form.submit()" class="portal-input min-h-[40px] text-sm">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        @endforeach
    </div>
    <div class="hidden md:block overflow-x-auto rounded-xl border border-outline-variant/30 admin-card soft-shadow">
        <table class="w-full text-sm">
            <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted"><tr><th class="p-3 text-left">Phone</th><th class="p-3 text-left">Title</th><th class="p-3 text-left">Status</th><th class="p-3 text-left">Created</th></tr></thead>
            <tbody>
                @foreach ($tickets as $ticket)
                    <tr class="border-t border-outline-variant/20 text-on-surface dark:text-inverse-on-surface">
                        <td class="p-3 font-mono">{{ $ticket->user?->phone_number }}</td>
                        <td class="p-3">{{ $ticket->title }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}">@csrf @method('PATCH')
                                <select name="status" onchange="this.form.submit()" class="portal-input min-h-[36px] py-1 text-sm">
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="p-3 admin-card-muted">{{ $ticket->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
</div>
@endsection
