<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalNotification;
use App\Models\RadAcct;
use App\Models\User;
use App\Services\LiveHotspotUsageService;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Fast path: no MikroTik API or RADIUS writes (those run on Connect + cron).
        $activePackage = PackageUsage::activePurchaseForDisplay($user);

        $usernames = $user->phone_number
            ? PackageUsage::usernameVariantsFor($user->phone_number)
            : [];

        if ($activePackage?->mikrotik_username) {
            $usernames[] = $activePackage->mikrotik_username;
            $usernames = array_values(array_unique($usernames));
        }

        $activeSessions = $usernames === []
            ? collect()
            : RadAcct::query()
                ->active()
                ->whereIn('username', $usernames)
                ->orderByDesc('acctstarttime')
                ->get();

        $isConnected = $activeSessions->isNotEmpty();

        $isUnlimitedData = $activePackage?->hasUnlimitedData() ?? false;

        $dataLimitBytes = $activePackage && ! $isUnlimitedData
            ? PackageUsage::dataLimitBytesFor($activePackage)
            : 0;

        $usageUser = $activePackage
            ? HotspotIdentity::usageUsernameFor($user, $activePackage)
            : null;

        $bytesRemaining = $activePackage && ! $isUnlimitedData
            ? PackageUsage::bytesRemainingForDisplay($activePackage, $usageUser)
            : null;

        if ($isUnlimitedData) {
            $dataRemainingGb = 'Unlimited';
            $totalPlanGb = 'Unlimited';
            $percentRemaining = 100;
        } else {
            $dataRemainingGb = round(($bytesRemaining ?? 0) / 1073741824, 1);
            $totalPlanGb = $dataLimitBytes > 0
                ? round($dataLimitBytes / 1073741824, 2)
                : 0;
            $percentRemaining = $dataLimitBytes > 0
                ? min(100, max(0, (($bytesRemaining ?? 0) / $dataLimitBytes) * 100))
                : 0;
        }

        $chartCircumference = 251.2;
        $chartStrokeOffset = $chartCircumference * (1 - $percentRemaining / 100);

        $wifiSpeedMbps = $activePackage?->speed_mbps;

        $displayName = $this->displayName($user);

        $announcement = PortalNotification::query()
            ->where('is_global', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        $validityLabel = $activePackage?->validityLabel() ?? '—';
        $planExpiresAt = $activePackage?->expires_at;
        $blockConnect = \App\Support\PortalStatus::shouldBlockConnect();

        $liveUsage = $activePackage !== null
            ? app(LiveHotspotUsageService::class)->snapshot($user)
            : null;

        return view('portal.dashboard', [
            'user' => $user,
            'activePackage' => $activePackage,
            'validityLabel' => $validityLabel,
            'planExpiresAt' => $planExpiresAt,
            'blockConnect' => $blockConnect,
            'activeSessions' => $activeSessions,
            'isConnected' => $isConnected,
            'announcement' => $announcement,
            'displayName' => $displayName,
            'dataRemainingGb' => $dataRemainingGb,
            'totalPlanGb' => $totalPlanGb,
            'hasActivePlan' => $activePackage !== null,
            'isUnlimitedData' => $isUnlimitedData,
            'chartStrokeOffset' => $chartStrokeOffset,
            'wifiSpeedMbps' => $wifiSpeedMbps,
            'liveUsage' => $liveUsage,
            'liveUsagePollSeconds' => (int) config('tesnet.portal_live_usage_poll_seconds', 15),
        ]);
    }

    public function liveUsage(Request $request, LiveHotspotUsageService $live): JsonResponse
    {
        return response()->json($live->snapshot($request->user()));
    }

    public function aboutHotspot(Request $request): View
    {
        return view('portal.about-hotspot', [
            'user' => $request->user(),
            'loginUrl' => config('services.mikrotik.login_url'),
        ]);
    }

    public function support(): View
    {
        return view('portal.support.index');
    }

    protected function displayName(User $user): string
    {
        if ($user->name && $user->name !== $user->phone_number && ! str_contains($user->name, '@')) {
            $parts = explode(' ', trim($user->name));

            return $parts[0] ?? 'User';
        }

        $phone = $user->phone_number ?? '';

        if ($phone !== '') {
            return $phone;
        }

        return 'User';
    }
}
