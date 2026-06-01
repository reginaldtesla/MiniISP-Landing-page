@extends('portal.layouts.dashboard')

@section('title', 'My Devices — TesNet')

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface mb-2">My devices</h1>
    <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant mb-6">
        You can use up to <strong>{{ $deviceLimit }}</strong> device(s) at the same time.
        Disconnect a session here if you need to free a slot on another phone or laptop.
    </p>

    @if ($sessions->isEmpty())
        <div class="bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 p-6 text-center">
            <p class="font-body-md text-on-surface-variant dark:text-outline-variant">No active Wi‑Fi sessions right now.</p>
            <a href="{{ route('portal.dashboard') }}" class="inline-flex mt-4 min-h-[44px] px-5 py-2 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm">Back to dashboard</a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($sessions as $s)
                <div class="bg-surface-container dark:bg-inverse-surface rounded-xl border border-surface-variant/50 dark:border-outline/30 p-4 flex flex-wrap gap-3 items-center justify-between">
                    <div class="min-w-0">
                        <p class="font-mono font-label-sm text-primary dark:text-primary-fixed-dim">{{ $s->callingstationid ?: 'Unknown MAC' }}</p>
                        <p class="font-body-md text-body-md text-on-surface-variant dark:text-outline-variant text-sm mt-1">
                            Started {{ $s->acctstarttime?->format('M j, g:i A') }} · {{ $s->formattedDataUsed() }} used
                            @if ($s->framedipaddress)
                                · IP {{ $s->framedipaddress }}
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('portal.devices.disconnect', $s) }}" onsubmit="return confirm('Disconnect this device?')">
                        @csrf
                        <button type="submit" class="min-h-[44px] px-4 py-2 rounded-lg border border-error-container text-error font-label-sm text-label-sm">
                            Disconnect
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
