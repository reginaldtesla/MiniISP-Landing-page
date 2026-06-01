<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PortalSetting;
use App\Support\PortalStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = PortalSetting::current();

        return view('admin.portal-settings.edit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'outage_enabled' => ['nullable', 'boolean'],
            'outage_message' => ['nullable', 'string', 'max:2000'],
            'block_purchases' => ['nullable', 'boolean'],
            'block_connect' => ['nullable', 'boolean'],
        ]);

        $settings = PortalSetting::current();
        $settings->fill([
            'outage_enabled' => (bool) ($validated['outage_enabled'] ?? false),
            'outage_message' => $validated['outage_message'] ?? null,
            'block_purchases' => (bool) ($validated['block_purchases'] ?? false),
            'block_connect' => (bool) ($validated['block_connect'] ?? false),
        ]);
        $settings->save();

        PortalStatus::clearCache();

        return back()->with('status', 'Portal settings updated.');
    }
}

