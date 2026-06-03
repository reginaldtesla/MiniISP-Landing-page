<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalNotification;
use App\Models\RadAcct;
use App\Models\User;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        PackageUsage::consolidateActivePurchases($user, touchRouter: false);

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
                ->limit(1)
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
            $dataUsedGb = null;
            $totalPlanGb = 'Unlimited';
            $percentRemaining = 100;
            $percentUsed = 0;
        } else {
            $dataRemainingGb = round(($bytesRemaining ?? 0) / 1073741824, 1);
            $totalPlanGb = $dataLimitBytes > 0
                ? round($dataLimitBytes / 1073741824, 2)
                : 0;
            $dataUsedGb = $dataLimitBytes > 0
                ? round(max(0, $dataLimitBytes - ($bytesRemaining ?? 0)) / 1073741824, 2)
                : 0;
            $percentRemaining = $dataLimitBytes > 0
                ? (int) min(100, max(0, round((($bytesRemaining ?? 0) / $dataLimitBytes) * 100)))
                : 0;
            $percentUsed = 100 - $percentRemaining;
        }

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

        return view('portal.dashboard', [
            'user' => $user,
            'activePackage' => $activePackage,
            'validityLabel' => $validityLabel,
            'planExpiresAt' => $planExpiresAt,
            'blockConnect' => $blockConnect,
            'isConnected' => $isConnected,
            'announcement' => $announcement,
            'displayName' => $displayName,
            'dataRemainingGb' => $dataRemainingGb,
            'totalPlanGb' => $totalPlanGb,
            'hasActivePlan' => $activePackage !== null,
            'isUnlimitedData' => $isUnlimitedData,
            'dataUsedGb' => $dataUsedGb,
            'percentRemaining' => $percentRemaining,
            'percentUsed' => $percentUsed,
        ]);
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
