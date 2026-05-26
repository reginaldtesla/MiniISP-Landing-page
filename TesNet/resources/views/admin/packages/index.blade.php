@extends('admin.layouts.hub')

@section('title', 'Packages — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface mb-1">Data Packages</h1>
            <p class="font-body-md text-body-md admin-card-muted">Define plans, pricing, speed caps, and how long purchased data stays valid.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.packages.create') }}"
                class="min-h-[48px] inline-flex items-center gap-2 px-5 rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm hover:opacity-90 transition-colors active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">add</span>
                New package
            </a>
            <a href="{{ route('admin.packages.create', ['special' => 1]) }}"
                class="min-h-[48px] inline-flex items-center gap-2 px-5 rounded-lg border-2 border-secondary text-secondary dark:border-secondary-fixed-dim dark:text-secondary-fixed-dim font-label-sm text-label-sm hover:bg-secondary-container/20 transition-colors active:scale-[0.98]">
                <span class="material-symbols-outlined text-[20px]">celebration</span>
                Special day offer
            </a>
        </div>
    </div>

    @if ($packages->isEmpty())
        <div class="admin-card rounded-xl p-8 text-center border border-outline-variant/20">
            <p class="font-body-md admin-card-muted mb-4">No packages yet. Run <code class="text-primary dark:text-primary-fixed-dim">php artisan db:seed</code> or create one.</p>
            <a href="{{ route('admin.packages.create') }}" class="text-primary dark:text-primary-fixed-dim font-semibold hover:underline">Create your first package</a>
        </div>
    @else
        <div class="hidden md:block overflow-x-auto rounded-xl border border-outline-variant/30 admin-card soft-shadow">
            <table class="w-full text-sm font-body-md">
                <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                    <tr>
                        <th class="p-3 text-left">Order</th>
                        <th class="p-3 text-left">Name</th>
                        <th class="p-3 text-left">Data</th>
                        <th class="p-3 text-left">Price</th>
                        <th class="p-3 text-left">Speed</th>
                        <th class="p-3 text-left">Duration</th>
                        <th class="p-3 text-left">Type</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($packages as $pkg)
                        <tr class="border-t border-outline-variant/20">
                            <td class="p-3 admin-card-muted">{{ $pkg->sort_order }}</td>
                            <td class="p-3">
                                <span class="admin-card-strong font-medium">{{ $pkg->name }}</span>
                                <span class="block font-label-sm admin-card-muted">{{ $pkg->slug }}</span>
                            </td>
                            <td class="p-3 admin-card-strong">{{ $pkg->data_label }}</td>
                            <td class="p-3 admin-card-strong">GH¢{{ number_format($pkg->price, 2) }}</td>
                            <td class="p-3 admin-card-muted">{{ $pkg->speed_mbps ? $pkg->speed_mbps.' Mbps' : '—' }}</td>
                            <td class="p-3 admin-card-muted">{{ $pkg->adminDurationLabel() }}</td>
                            <td class="p-3">
                                @if ($pkg->is_special_offer)
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-tertiary-fixed/30 text-tertiary dark:text-tertiary-fixed-dim font-label-sm text-[11px]">Special</span>
                                    @if ($status = $pkg->specialOfferStatus())
                                        <span class="block font-label-sm admin-card-muted mt-0.5 capitalize">{{ $status }}</span>
                                    @endif
                                @else
                                    <span class="admin-card-muted font-label-sm">Standard</span>
                                @endif
                            </td>
                            <td class="p-3">
                                @if ($pkg->is_active)
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-secondary-container/40 text-secondary dark:text-secondary-fixed-dim font-label-sm text-[11px]">Live</span>
                                @else
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-outline-variant/30 admin-card-muted font-label-sm text-[11px]">Hidden</span>
                                @endif
                            </td>
                            <td class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.packages.edit', $pkg) }}" class="text-primary dark:text-primary-fixed-dim font-label-sm hover:underline">Edit</a>
                                <form method="POST" action="{{ route('admin.packages.destroy', $pkg) }}" class="inline ml-2" onsubmit="return confirm('Remove this package from the store?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-error font-label-sm hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-3">
            @foreach ($packages as $pkg)
                <div class="admin-card rounded-xl p-4 border border-outline-variant/20">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <p class="font-title-md text-primary dark:text-primary-fixed-dim">{{ $pkg->name }}</p>
                            <p class="font-headline-lg-mobile admin-card-strong">{{ $pkg->data_label }} · GH¢{{ number_format($pkg->price, 2) }}</p>
                        </div>
                        @if ($pkg->is_active)
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-secondary-container/40 text-secondary dark:text-secondary-fixed-dim">Live</span>
                        @else
                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-outline-variant/30 admin-card-muted">Hidden</span>
                        @endif
                    </div>
                    <p class="font-label-sm admin-card-muted mt-2">
                        {{ $pkg->adminDurationLabel() }}
                        @if($pkg->speed_mbps)· {{ $pkg->speed_mbps }} Mbps @endif
                        @if($pkg->is_special_offer)· Special @if($s = $pkg->specialOfferStatus()) ({{ $s }}) @endif @endif
                    </p>
                    <div class="flex gap-4 mt-3">
                        <a href="{{ route('admin.packages.edit', $pkg) }}" class="text-primary dark:text-primary-fixed-dim font-label-sm">Edit</a>
                        <form method="POST" action="{{ route('admin.packages.destroy', $pkg) }}" onsubmit="return confirm('Remove this package?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-error font-label-sm">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
