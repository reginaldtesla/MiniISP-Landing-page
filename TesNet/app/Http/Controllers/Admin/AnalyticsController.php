<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackagePurchase;
use App\Models\RadAcct;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $now = now();
        $from = $now->copy()->subDays(14)->startOfDay();

        $revenueByDay = Transaction::query()
            ->selectRaw("DATE(paid_at) as day, SUM(amount) as revenue, COUNT(*) as count")
            ->where('status', 'success')
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', $from)
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->orderBy('day')
            ->get();

        $totalRevenue = Transaction::query()
            ->where('status', 'success')
            ->sum('amount');

        $last24hUsage = RadAcct::query()
            ->whereNotNull('acctupdatetime')
            ->where('acctupdatetime', '>=', $now->copy()->subDay())
            ->sum(DB::raw('COALESCE(acctinputoctets,0) + COALESCE(acctoutputoctets,0)'));

        $topPackages = PackagePurchase::query()
            ->selectRaw('package_name, package_slug, COUNT(*) as purchases')
            ->where('status', 'active')
            ->groupBy('package_name', 'package_slug')
            ->orderByDesc('purchases')
            ->limit(10)
            ->get();

        $activeSessions = RadAcct::query()->active()->count();

        $revenueByChannel = Transaction::query()
            ->selectRaw("COALESCE(channel, 'unknown') as channel, SUM(amount) as revenue, COUNT(*) as count")
            ->where('status', 'success')
            ->groupBy('channel')
            ->orderByDesc('revenue')
            ->get();

        $revenueByType = Transaction::query()
            ->selectRaw('type, SUM(amount) as revenue, COUNT(*) as count')
            ->where('status', 'success')
            ->groupBy('type')
            ->orderByDesc('revenue')
            ->get();

        return view('admin.analytics.index', [
            'revenueByDay' => $revenueByDay,
            'totalRevenue' => $totalRevenue,
            'activeSessions' => $activeSessions,
            'last24hUsageBytes' => (int) $last24hUsage,
            'topPackages' => $topPackages,
            'revenueByChannel' => $revenueByChannel,
            'revenueByType' => $revenueByType,
        ]);
    }
}

