<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalNotification;
use App\Models\RadAcct;
use App\Models\User;
use App\Services\MikrotikApiService;
use App\Support\BytesFormat;
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
        $usage = $this->usageSnapshot($user);

        $displayName = $this->displayName($user);

        $announcement = PortalNotification::query()
            ->where('is_global', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        $activePackage = $usage['active_package'];
        $validityLabel = $activePackage?->validityLabel() ?? '—';
        $planExpiresAt = $activePackage?->expires_at;
        $blockConnect = \App\Support\PortalStatus::shouldBlockConnect();

        return view('portal.dashboard', [
            'user' => $user,
            'activePackage' => $activePackage,
            'validityLabel' => $validityLabel,
            'planExpiresAt' => $planExpiresAt,
            'blockConnect' => $blockConnect,
            'isConnected' => $usage['is_connected'],
            'announcement' => $announcement,
            'displayName' => $displayName,
            'dataRemainingLabel' => $usage['data_remaining_label'],
            'dataUsedLabel' => $usage['data_used_label'],
            'totalPlanLabel' => $usage['total_plan_label'],
            'hasActivePlan' => $usage['has_active_plan'],
            'isUnlimitedData' => $usage['is_unlimited'],
            'percentRemaining' => $usage['percent_remaining'],
            'percentUsed' => $usage['percent_used'],
            'usageRefreshIntervalMs' => $usage['has_active_plan']
                ? config('tesnet.dashboard_usage_refresh_seconds', 60) * 1000
                : 0,
        ]);
    }

    public function dataUsage(Request $request): JsonResponse
    {
        $usage = $this->usageSnapshot($request->user());
        unset($usage['active_package']);

        return response()->json($usage);
    }

    /**
     * @return array<string, mixed>
     */
    protected function usageSnapshot(User $user): array
    {
        $activePackage = PackageUsage::reconcileActivePurchaseWithRouter($user);

        $usernames = $user->phone_number
            ? PackageUsage::usernameVariantsFor($user->phone_number)
            : [];

        if ($activePackage?->mikrotik_username) {
            $usernames[] = $activePackage->mikrotik_username;
            $usernames = array_values(array_unique($usernames));
        }

        $isConnected = $usernames !== [] && RadAcct::query()
            ->active()
            ->whereIn('username', $usernames)
            ->exists();

        $isUnlimitedData = $activePackage?->hasUnlimitedData() ?? false;

        $dataLimitBytes = $activePackage && ! $isUnlimitedData
            ? PackageUsage::dataLimitBytesFor($activePackage)
            : 0;

        $usageUser = $activePackage
            ? HotspotIdentity::usageUsernameFor($user, $activePackage)
            : null;

        $bytesRemaining = $activePackage && ! $isUnlimitedData
            ? (app(MikrotikApiService::class)->isEnabled()
                ? PackageUsage::bytesRemainingWithRouter($activePackage, $usageUser)
                : PackageUsage::bytesRemainingForDisplay($activePackage, $usageUser))
            : null;

        $dataRemainingLabel = '—';
        $dataUsedLabel = '—';
        $totalPlanLabel = '—';
        $percentRemaining = 0;
        $percentUsed = 0;

        if ($isUnlimitedData) {
            $dataRemainingLabel = 'Unlimited';
            $totalPlanLabel = 'Unlimited';
            $percentRemaining = 100;
        } elseif ($activePackage && $dataLimitBytes > 0) {
            $bytesUsed = max(0, $dataLimitBytes - (int) ($bytesRemaining ?? 0));
            $dataRemainingLabel = BytesFormat::planDataAmount((int) ($bytesRemaining ?? 0));
            $dataUsedLabel = BytesFormat::planDataAmount($bytesUsed);
            $totalPlanLabel = BytesFormat::planDataAmount($dataLimitBytes);
            $percentRemaining = (int) min(100, max(0, round(((($bytesRemaining ?? 0) / $dataLimitBytes) * 100))));
            $percentUsed = 100 - $percentRemaining;
        }

        return [
            'has_active_plan' => $activePackage !== null,
            'is_unlimited' => $isUnlimitedData,
            'is_connected' => $isConnected,
            'package_name' => $activePackage?->package_name,
            'data_remaining_label' => $dataRemainingLabel,
            'data_used_label' => $dataUsedLabel,
            'total_plan_label' => $totalPlanLabel,
            'percent_remaining' => $percentRemaining,
            'percent_used' => $percentUsed,
            'refreshed_at' => now()->toIso8601String(),
            'refreshed_at_label' => now()->timezone(config('app.timezone'))->format('g:i A'),
            'active_package' => $activePackage,
        ];
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
