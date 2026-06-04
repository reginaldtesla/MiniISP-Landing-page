@extends('admin.layouts.hub')

@section('title', 'Manual Payments — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface">Manual Payments</h1>
            <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">Requests from students when Paystack is down (MoMo/Airtime).</p>
            <a href="{{ route('admin.vouchers.index') }}" class="inline-flex items-center gap-1 mt-2 text-primary dark:text-primary-fixed-dim font-label-sm hover:underline">
                <span class="material-symbols-outlined text-[18px]">confirmation_number</span>
                Create instant voucher codes instead
            </a>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.manual-payments.index', ['status' => 'pending']) }}" class="min-h-[44px] px-4 py-2 rounded-lg {{ $status === 'pending' ? 'bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed' : 'border border-outline-variant/30 admin-card-muted' }} font-label-sm text-label-sm">Pending</a>
            <a href="{{ route('admin.manual-payments.index', ['status' => 'approved']) }}" class="min-h-[44px] px-4 py-2 rounded-lg {{ $status === 'approved' ? 'bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed' : 'border border-outline-variant/30 admin-card-muted' }} font-label-sm text-label-sm">Approved</a>
            <a href="{{ route('admin.manual-payments.index', ['status' => 'rejected']) }}" class="min-h-[44px] px-4 py-2 rounded-lg {{ $status === 'rejected' ? 'bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed' : 'border border-outline-variant/30 admin-card-muted' }} font-label-sm text-label-sm">Rejected</a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-outline-variant/30 admin-card soft-shadow">
        <table class="w-full text-sm font-body-md min-w-[900px]">
            <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                <tr>
                    <th class="p-3 text-left">Student</th>
                    <th class="p-3 text-left">Type</th>
                    <th class="p-3 text-left">Package</th>
                    <th class="p-3 text-left">Amount</th>
                    <th class="p-3 text-left">Method</th>
                    <th class="p-3 text-left">Ref</th>
                    <th class="p-3 text-left">Proof</th>
                    <th class="p-3 text-left">Submitted</th>
                    <th class="p-3 text-left">Reviewed</th>
                    <th class="p-3 text-left">Tx</th>
                    <th class="p-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $r)
                    <tr class="border-t border-outline-variant/20 admin-card-strong align-top">
                        <td class="p-3 font-mono">{{ $r->user?->phone_number }}</td>
                        <td class="p-3">{{ $r->type }}</td>
                        <td class="p-3">{{ $r->package_slug ?: '—' }}</td>
                        <td class="p-3">GH¢{{ number_format($r->amount, 2) }}</td>
                        <td class="p-3">{{ $r->payment_method }}{{ $r->provider ? ' · '.$r->provider : '' }}</td>
                        <td class="p-3">{{ $r->reference ?: '—' }}</td>
                        <td class="p-3">
                            @if ($r->proof_path)
                                <a href="{{ route('admin.manual-payments.proof', $r) }}" class="text-primary dark:text-primary-fixed-dim font-label-sm hover:underline">View</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-3 admin-card-muted">{{ $r->created_at->format('M j, H:i') }}</td>
                        <td class="p-3 admin-card-muted">
                            @if ($r->reviewed_at)
                                {{ $r->reviewed_at->format('M j, H:i') }} · {{ $r->reviewer?->phone_number ?: 'admin' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="p-3 font-mono text-xs">{{ $r->transaction_id ?: '—' }}</td>
                        <td class="p-3">
                            @if ($status === 'pending')
                                <div class="flex flex-col gap-2">
                                    <form method="POST" action="{{ route('admin.manual-payments.approve', $r) }}" onsubmit="return confirm('Approve and activate plan?')">
                                        @csrf
                                        <input name="admin_note" class="w-full min-h-[40px] rounded-lg bg-surface dark:bg-admin-elevated-high border border-outline-variant/30 px-3 text-sm admin-card-strong" placeholder="Admin note (optional)">
                                        <button class="mt-2 min-h-[40px] px-4 rounded-lg bg-secondary text-on-secondary dark:bg-secondary-fixed-dim dark:text-on-secondary-fixed font-label-sm text-label-sm w-full">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.manual-payments.reject', $r) }}" onsubmit="return confirm('Reject this request?')">
                                        @csrf
                                        <input name="admin_note" required class="w-full min-h-[40px] rounded-lg bg-surface dark:bg-admin-elevated-high border border-outline-variant/30 px-3 text-sm admin-card-strong" placeholder="Reason (required)">
                                        <button class="mt-2 min-h-[40px] px-4 rounded-lg border border-error-container text-error font-label-sm text-label-sm w-full">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="admin-card-muted text-sm">{{ $r->admin_note ?: '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="p-8 text-center admin-card-muted">No requests.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $requests->links() }}</div>
</div>
@endsection

