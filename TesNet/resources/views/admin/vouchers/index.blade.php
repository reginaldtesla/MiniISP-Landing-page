@extends('admin.layouts.hub')

@section('title', 'Voucher Codes — TESNET Admin')

@section('content')
<div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 max-w-container-max w-full mr-auto">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="font-headline-lg-mobile md:font-headline-lg text-headline-lg-mobile md:text-headline-lg text-on-surface dark:text-inverse-on-surface">Voucher Codes</h1>
            <p class="font-body-md text-body-md admin-card-muted text-sm mt-1">
                Create a code for a package, give it to a student, they enter it on their dashboard for instant internet.
            </p>
            <a href="{{ route('admin.manual-payments.index') }}" class="inline-flex items-center gap-1 mt-2 text-primary dark:text-primary-fixed-dim font-label-sm hover:underline">
                <span class="material-symbols-outlined text-[18px]">arrow_back</span>
                MoMo proof queue
            </a>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.vouchers.index', ['status' => 'available']) }}" class="min-h-[44px] px-4 py-2 rounded-lg {{ $status === 'available' ? 'bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed' : 'border border-outline-variant/30 admin-card-muted' }} font-label-sm text-label-sm">Available</a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'redeemed']) }}" class="min-h-[44px] px-4 py-2 rounded-lg {{ $status === 'redeemed' ? 'bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed' : 'border border-outline-variant/30 admin-card-muted' }} font-label-sm text-label-sm">Redeemed</a>
            <a href="{{ route('admin.vouchers.index', ['status' => 'revoked']) }}" class="min-h-[44px] px-4 py-2 rounded-lg {{ $status === 'revoked' ? 'bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed' : 'border border-outline-variant/30 admin-card-muted' }} font-label-sm text-label-sm">Revoked</a>
        </div>
    </div>

    @if (session('voucher_codes'))
        <div class="mb-6 rounded-xl border border-secondary-container bg-secondary-container/20 dark:bg-secondary-container/10 p-4 sm:p-5">
            <p class="font-label-sm text-label-sm text-secondary dark:text-secondary-fixed-dim font-semibold mb-2 flex items-center gap-2">
                <span class="material-symbols-outlined fill text-[20px]">content_copy</span>
                New code{{ count(session('voucher_codes')) > 1 ? 's' : '' }} — share with the student
            </p>
            <div class="space-y-2">
                @foreach (session('voucher_codes') as $newCode)
                    <div class="flex flex-wrap items-center gap-3">
                        <code class="text-lg sm:text-xl font-mono font-bold text-on-surface dark:text-inverse-on-surface tracking-wide">{{ $newCode }}</code>
                        <button type="button"
                                onclick="navigator.clipboard.writeText('{{ $newCode }}'); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 1500);"
                                class="min-h-[40px] px-3 rounded-lg border border-outline-variant/30 admin-card-muted font-label-sm hover:bg-surface-container-high transition-colors">
                            Copy
                        </button>
                    </div>
                @endforeach
            </div>
            <p class="font-label-sm text-label-sm admin-card-muted mt-3">Student enters this on their portal Home page under “Have a code?”</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,360px)_1fr] gap-6 mb-8">
        <div class="admin-card rounded-xl border border-outline-variant/30 soft-shadow p-5">
            <h2 class="font-title-md text-title-md admin-card-strong mb-1">Create code</h2>
            <p class="font-label-sm text-label-sm admin-card-muted mb-4">Each code works once for the selected package.</p>
            <form method="POST" action="{{ route('admin.vouchers.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label for="package_slug" class="block font-label-sm text-label-sm admin-card-muted mb-1">Package</label>
                    <select id="package_slug" name="package_slug" required
                            class="w-full min-h-[48px] rounded-lg border border-outline-variant/40 bg-surface dark:bg-admin-elevated-high px-3 font-body-md">
                        <option value="">Select package…</option>
                        @foreach ($packages as $pkg)
                            <option value="{{ $pkg->slug }}" @selected(old('package_slug') === $pkg->slug)>
                                {{ $pkg->name }} · {{ $pkg->data_label }} · GH¢{{ number_format($pkg->price, 2) }}
                            </option>
                        @endforeach
                    </select>
                    @error('package_slug')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="quantity" class="block font-label-sm text-label-sm admin-card-muted mb-1">How many codes</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="50" value="{{ old('quantity', 1) }}"
                           class="w-full min-h-[48px] rounded-lg border border-outline-variant/40 bg-surface dark:bg-admin-elevated-high px-3 font-body-md"/>
                </div>
                <div>
                    <label for="admin_note" class="block font-label-sm text-label-sm admin-card-muted mb-1">Note (optional)</label>
                    <input type="text" id="admin_note" name="admin_note" value="{{ old('admin_note') }}" maxlength="2000" placeholder="e.g. Paid cash at desk"
                           class="w-full min-h-[48px] rounded-lg border border-outline-variant/40 bg-surface dark:bg-admin-elevated-high px-3 font-body-md"/>
                </div>
                <button type="submit"
                        class="w-full min-h-[48px] rounded-lg bg-primary text-on-primary dark:bg-primary-fixed-dim dark:text-on-primary-fixed font-label-sm text-label-sm font-semibold hover:opacity-90 transition-opacity active:scale-[0.98]">
                    Generate code
                </button>
            </form>
        </div>

        <div class="admin-card rounded-xl border border-outline-variant/30 soft-shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm font-body-md min-w-[720px]">
                    <thead class="bg-surface-container-high dark:bg-admin-elevated-high admin-card-muted">
                        <tr>
                            <th class="p-3 text-left">Code</th>
                            <th class="p-3 text-left">Package</th>
                            <th class="p-3 text-left">Amount</th>
                            <th class="p-3 text-left">Created</th>
                            <th class="p-3 text-left">Redeemed by</th>
                            <th class="p-3 text-left">Note</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vouchers as $v)
                            <tr class="border-t border-outline-variant/20 admin-card-strong align-top">
                                <td class="p-3 font-mono font-semibold tracking-wide">{{ $v->code }}</td>
                                <td class="p-3">
                                    <span class="block">{{ $v->package_name }}</span>
                                    <span class="font-label-sm admin-card-muted">{{ $v->package_slug }}</span>
                                </td>
                                <td class="p-3">GH¢{{ number_format($v->amount, 2) }}</td>
                                <td class="p-3 admin-card-muted">{{ $v->created_at->format('M j, H:i') }}</td>
                                <td class="p-3 admin-card-muted">
                                    @if ($v->redeemer)
                                        {{ $v->redeemer->phone_number }}
                                        <span class="block font-label-sm">{{ $v->redeemed_at?->format('M j, H:i') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="p-3 admin-card-muted max-w-[140px] truncate" title="{{ $v->admin_note }}">{{ $v->admin_note ?: '—' }}</td>
                                <td class="p-3">
                                    @if ($v->status === 'available')
                                        <form method="POST" action="{{ route('admin.vouchers.revoke', $v) }}" onsubmit="return confirm('Revoke this code?');">
                                            @csrf
                                            <button type="submit" class="text-error font-label-sm hover:underline">Revoke</button>
                                        </form>
                                    @else
                                        <span class="font-label-sm admin-card-muted capitalize">{{ $v->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center admin-card-muted">No {{ $status }} codes yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($vouchers->hasPages())
                <div class="p-4 border-t border-outline-variant/20">{{ $vouchers->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
