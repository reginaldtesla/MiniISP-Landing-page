<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\RadAcct;
use App\Services\PackageQuotaService;
use App\Support\PackageUsage;
use App\Services\SessionDisconnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $usernames = $this->allowedSessionUsernames($user);

        $sessions = $usernames === []
            ? collect()
            : RadAcct::query()
                ->active()
                ->whereIn('username', $usernames)
                ->orderByDesc('acctstarttime')
                ->get();

        return view('portal.devices.index', [
            'sessions' => $sessions,
            'deviceLimit' => $request->user()->device_limit,
        ]);
    }

    public function disconnect(Request $request, RadAcct $session, SessionDisconnectService $disconnect, PackageQuotaService $quota): RedirectResponse
    {
        if (! in_array($session->username, $this->allowedSessionUsernames($request->user()), true)) {
            abort(403);
        }

        if ($session->acctstoptime !== null) {
            return back()->with('status', 'That session is already disconnected.');
        }

        $result = $disconnect->forceDisconnect($session);

        if ($result->succeeded()) {
            $quota->syncForUser($request->user(), force: true);

            return back()->with('status', $result->userMessage('Device disconnected. You can connect again from the dashboard.'));
        }

        return back()->withErrors(['devices' => $result->userMessage()]);
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSessionUsernames(\App\Models\User $user): array
    {
        $usernames = $user->phone_number
            ? PackageUsage::usernameVariantsFor($user->phone_number)
            : [];

        $active = PackageUsage::activePurchaseForDisplay($user);

        if ($active?->mikrotik_username) {
            $usernames[] = $active->mikrotik_username;
        }

        return array_values(array_unique($usernames));
    }
}
