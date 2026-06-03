<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\RadAcct;
use App\Services\PackageQuotaService;
use App\Services\SessionDisconnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $phone = $request->user()->phone_number;

        $sessions = RadAcct::query()
            ->active()
            ->where('username', $phone)
            ->orderByDesc('acctstarttime')
            ->get();

        return view('portal.devices.index', [
            'sessions' => $sessions,
            'deviceLimit' => $request->user()->device_limit,
        ]);
    }

    public function disconnect(Request $request, RadAcct $session, SessionDisconnectService $disconnect, PackageQuotaService $quota): RedirectResponse
    {
        if ($session->username !== $request->user()->phone_number) {
            abort(403);
        }

        if ($session->acctstoptime !== null) {
            return back()->with('status', 'That session is already disconnected.');
        }

        $result = $disconnect->forceDisconnect($session);

        if ($result->succeeded()) {
            $quota->syncForUser($request->user());

            return back()->with('status', $result->userMessage('Device disconnected. You can connect again from the dashboard.'));
        }

        return back()->withErrors(['devices' => $result->userMessage()]);
    }
}
