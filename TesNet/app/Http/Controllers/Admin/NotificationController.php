<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        $recentMessages = PortalNotification::query()
            ->latest()
            ->limit(30)
            ->get();

        return view('admin.notifications.index', [
            'recentMessages' => $recentMessages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
            'is_global' => ['sometimes', 'boolean'],
            'expire_24_hours' => ['sometimes', 'boolean'],
            'type' => ['nullable', 'in:info,warning,success'],
        ]);

        $expiresAt = $request->boolean('expire_24_hours')
            ? now()->addHours(24)
            : null;

        PortalNotification::query()->create([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'info',
            'is_global' => $request->boolean('is_global'),
            'expires_at' => $expiresAt,
        ]);

        return redirect()->route('admin.notifications.index')
            ->with('status', 'Announcement sent to students.');
    }

    public function destroy(PortalNotification $notification): RedirectResponse
    {
        $notification->delete();

        return redirect()->route('admin.notifications.index')
            ->with('status', 'Announcement removed.');
    }
}
