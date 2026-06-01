@extends('admin.layouts.hub')

@section('title', 'Live Sessions — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface mb-2">Active Sessions</h1>
    <p class="font-body-md text-body-md admin-card-muted mb-6 text-sm">
        FreeRADIUS <code class="text-primary dark:text-primary-fixed-dim">radacct</code>
        @if ($mikrotikEnabled) · MikroTik disconnect enabled @endif
    </p>
    <div class="md:hidden space-y-3">
        @forelse ($sessions as $s)
            <div class="admin-card rounded-xl p-4 soft-shadow border border-outline-variant/20">
                <p class="font-mono font-label-sm text-primary dark:text-primary-fixed-dim">{{ $s->username }}</p>
                <p class="font-body-md text-sm admin-card-muted mt-1">{{ $s->formattedDataUsed() }} · {{ $s->acctstarttime?->format('M j, H:i') }}</p>
                <form method="POST" action="{{ route('admin.sessions.disconnect', $s) }}" class="mt-3" onsubmit="return confirm('Disconnect?')">@csrf
                    <button class="text-error font-label-sm text-label-sm">Force Disconnect</button>
                </form>
            </div>
        @empty
            <p class="text-center admin-card-muted py-8">No active sessions.</p>
        @endforelse
    </div>
    <div class="hidden md:block overflow-x-auto rounded-xl border border-outline-variant/30 admin-card soft-shadow">
        <table class="w-full text-sm font-body-md">
            <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                <tr>
                    <th class="p-3 text-left">Phone</th><th class="p-3 text-left">Started</th><th class="p-3 text-left">Data</th><th class="p-3 text-left">IP</th><th class="p-3 text-left">MAC</th><th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sessions as $s)
                    <tr class="border-t border-outline-variant/20 admin-card-strong">
                        <td class="p-3 font-mono">{{ $s->username }}</td>
                        <td class="p-3">{{ $s->acctstarttime?->format('M j, H:i') }}</td>
                        <td class="p-3">{{ $s->formattedDataUsed() }}</td>
                        <td class="p-3">{{ $s->framedipaddress ?: '—' }}</td>
                        <td class="p-3 font-mono text-xs">{{ $s->callingstationid ?: '—' }}</td>
                        <td class="p-3">
                            <form method="POST" action="{{ route('admin.sessions.disconnect', $s) }}" onsubmit="return confirm('Disconnect?')">@csrf
                                <button class="text-error font-label-sm hover:underline">Disconnect</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $sessions->links() }}</div>
</div>
@endsection
