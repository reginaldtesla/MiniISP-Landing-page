<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RadAcct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalUsers = User::query()->where('is_admin', false)->count();
        $activeSessions = RadAcct::query()->active()->count();
        $packageRevenue = Transaction::query()
            ->where('status', 'success')
            ->where('type', 'package')
            ->sum('amount');
        $recentTransactions = Transaction::query()
            ->with('user')
            ->where('status', 'success')
            ->latest('paid_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'totalUsers' => $totalUsers,
            'activeSessions' => $activeSessions,
            'packageRevenue' => $packageRevenue,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
