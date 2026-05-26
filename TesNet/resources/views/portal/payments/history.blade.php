@extends('portal.layouts.dashboard')

@section('title', 'Payments — TesNet')

@section('content')
<div class="px-4 md:px-margin-desktop py-5 md:py-10 max-w-container-max w-full mr-auto">
    <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-background dark:text-inverse-on-surface mb-6">Payment History</h1>
    <div class="md:hidden space-y-3">
        @forelse ($transactions as $tx)
            <div class="bg-surface-container dark:bg-inverse-surface rounded-xl p-4 border border-surface-variant/50 dark:border-outline/30">
                <p class="font-label-sm text-label-sm text-on-surface-variant dark:text-outline-variant break-all">{{ $tx->paystack_reference }}</p>
                <p class="font-title-md text-title-md text-on-background dark:text-inverse-on-surface mt-1">GH¢{{ number_format($tx->amount, 2) }}</p>
                <div class="flex justify-between mt-2 text-sm">
                    <span class="text-on-surface-variant dark:text-outline-variant">{{ $tx->type === 'package' ? ($tx->package_slug ?? 'Package') : ucfirst(str_replace('_', ' ', $tx->type)) }}</span>
                    <span @class([
                        'font-label-sm px-2 py-0.5 rounded-full',
                        'bg-secondary-container/40 text-secondary dark:text-secondary-fixed-dim' => $tx->status === 'success',
                        'bg-tertiary-fixed text-tertiary dark:text-tertiary-fixed-dim' => $tx->status === 'pending',
                        'bg-error-container text-error' => $tx->status === 'failed',
                    ])>{{ ucfirst($tx->status) }}</span>
                </div>
                <p class="font-label-sm text-label-sm text-outline mt-1">{{ $tx->created_at->format('M j, Y H:i') }}</p>
            </div>
        @empty
            <p class="text-center text-on-surface-variant dark:text-outline-variant py-8">No payments yet.</p>
        @endforelse
    </div>
    <div class="hidden md:block overflow-x-auto rounded-xl border border-surface-variant/50 dark:border-outline/30 bg-surface-container dark:bg-inverse-surface shadow-sm">
        <table class="w-full text-left font-body-md text-body-md min-w-[600px]">
            <thead class="bg-surface-container-high dark:bg-on-background/50 text-on-surface-variant dark:text-outline-variant font-label-sm text-label-sm">
                <tr>
                    <th class="p-4">Reference</th>
                    <th class="p-4">Type</th>
                    <th class="p-4">Amount</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Date</th>
                </tr>
            </thead>
            <tbody class="text-on-background dark:text-inverse-on-surface">
                @forelse ($transactions as $tx)
                    <tr class="border-t border-surface-variant/40 dark:border-outline/20">
                        <td class="p-4 font-label-sm text-label-sm">{{ $tx->paystack_reference }}</td>
                        <td class="p-4">{{ $tx->type === 'package' ? ($tx->package_slug ?? 'package') : ucfirst(str_replace('_', ' ', $tx->type)) }}</td>
                        <td class="p-4">GH¢{{ number_format($tx->amount, 2) }}</td>
                        <td class="p-4">
                            <span @class([
                                'font-label-sm text-label-sm px-2 py-0.5 rounded-full',
                                'bg-secondary-container/40 text-secondary dark:text-secondary-fixed-dim' => $tx->status === 'success',
                                'bg-tertiary-fixed text-tertiary dark:text-tertiary-fixed-dim' => $tx->status === 'pending',
                                'bg-error-container text-error' => $tx->status === 'failed',
                            ])>{{ ucfirst($tx->status) }}</span>
                        </td>
                        <td class="p-4 text-on-surface-variant dark:text-outline-variant">{{ $tx->created_at->format('M j, Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-on-surface-variant dark:text-outline-variant">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $transactions->links() }}</div>
</div>
@endsection
