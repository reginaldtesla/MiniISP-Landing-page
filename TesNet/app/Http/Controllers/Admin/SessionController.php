<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RadAcct;
use App\Services\MikrotikApiService;
use App\Services\SessionDisconnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(): View
    {
        $sessions = RadAcct::query()
            ->active()
            ->orderByDesc('acctstarttime')
            ->paginate(25);

        return view('admin.sessions.index', [
            'sessions' => $sessions,
            'mikrotikEnabled' => app(MikrotikApiService::class)->isEnabled(),
        ]);
    }

    public function disconnect(RadAcct $session, SessionDisconnectService $disconnectService): RedirectResponse
    {
        $disconnectService->forceDisconnect($session);

        return back()->with('status', 'Session for '.$session->username.' has been disconnected.');
    }
}
