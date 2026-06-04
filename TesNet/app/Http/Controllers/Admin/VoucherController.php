<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPackage;
use App\Models\PackageVoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'available');
        $status = in_array($status, ['available', 'redeemed', 'revoked'], true) ? $status : 'available';

        $vouchers = PackageVoucher::query()
            ->with(['creator', 'redeemer'])
            ->where('status', $status)
            ->latest()
            ->paginate(25);

        $packages = DataPackage::query()->active()->ordered()->get(['slug', 'name', 'price', 'data_label']);

        return view('admin.vouchers.index', compact('vouchers', 'packages', 'status'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'package_slug' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $package = DataPackage::query()
            ->active()
            ->where('slug', $validated['package_slug'])
            ->first();

        if (! $package) {
            return back()->withErrors(['package_slug' => 'Pick an active package from the list.']);
        }

        $quantity = (int) ($validated['quantity'] ?? 1);
        $codes = [];

        for ($i = 0; $i < $quantity; $i++) {
            $voucher = PackageVoucher::query()->create([
                'code' => PackageVoucher::generateCode(),
                'package_slug' => $package->slug,
                'package_name' => $package->name,
                'amount' => $package->price,
                'amount_pesewas' => $package->amountPesewas(),
                'status' => PackageVoucher::STATUS_AVAILABLE,
                'admin_note' => $validated['admin_note'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $codes[] = $voucher->code;
        }

        $message = $quantity === 1
            ? 'Voucher created: '.$codes[0].' ('.$package->name.')'
            : 'Created '.$quantity.' vouchers for '.$package->name.'.';

        return back()
            ->with('status', $message)
            ->with('voucher_codes', $codes);
    }

    public function revoke(PackageVoucher $voucher): RedirectResponse
    {
        if ($voucher->status !== PackageVoucher::STATUS_AVAILABLE) {
            return back()->withErrors(['voucher' => 'Only unused codes can be revoked.']);
        }

        $voucher->update(['status' => PackageVoucher::STATUS_REVOKED]);

        return back()->with('status', 'Code '.$voucher->code.' revoked.');
    }
}
