<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PackageQuotaService;
use App\Services\SingleDeviceGuard;
use App\Support\HotspotIdentity;
use App\Support\PackageUsage;
use App\Support\PaystackCustomerEmail;
use App\Support\PhoneNumber;
use App\Support\PortalStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showRegister(): View
    {
        return view('portal.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $phone = PhoneNumber::normalize($request->input('phone_number'));

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! PhoneNumber::isValid($phone)) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Enter a valid Ghana mobile number (e.g. 0551234567).',
            ]);
        }

        if (User::query()->where('phone_number', $phone)->exists()) {
            return back()->withInput()->withErrors([
                'phone_number' => 'This phone number is already registered.',
            ]);
        }

        User::setPlainPasswordForRadius($validated['password']);

        $user = User::query()->create([
            'name' => $phone,
            'email' => PaystackCustomerEmail::forPhone($phone),
            'phone_number' => $phone,
            'password' => $validated['password'],
            'device_limit' => 1,
            'wallet_balance' => 0,
        ]);

        User::setPlainPasswordForRadius(null);

        Auth::login($user);
        $request->session()->regenerate();
        $this->finalizePortalLogin($request, $user, $validated['password']);

        return redirect()->route('portal.dashboard')
            ->with('status', 'Account created. Buy a data plan to get online.');
    }

    public function showLogin(): View
    {
        return view('portal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $phone = PhoneNumber::normalize($request->input('phone_number'));

        $credentials = [
            'phone_number' => $phone,
            'password' => $request->input('password'),
        ];

        if (! PhoneNumber::isValid($phone)) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Enter a valid Ghana mobile number.',
            ]);
        }

        $account = User::query()->where('phone_number', $phone)->first();

        if ($account?->isAdmin()) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Administrator accounts must use Admin Hub sign-in, not the student portal.',
            ]);
        }

        if ($account && $account->is_suspended) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Your account is suspended. Contact support.',
            ]);
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $this->finalizePortalLogin($request, $request->user(), (string) $request->input('password'));

            $user = $request->user();

            if (! $user->hasActiveDataPlan()) {
                return redirect()->route('portal.packages')
                    ->with('status', 'Your data is used up or you have no active plan. Choose a package to continue.');
            }

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()->withInput()->withErrors([
            'phone_number' => 'Invalid phone number or password.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    public function connectToWifi(Request $request, PackageQuotaService $quota): RedirectResponse|View
    {
        if (PortalStatus::shouldBlockConnect()) {
            return redirect()->route('portal.dashboard')
                ->withErrors(['wifi' => 'Connect is temporarily disabled. Please check the service notice or contact support.']);
        }

        $user = $request->user();
        $password = $this->wifiPasswordFromSession($request);

        if (! $password) {
            return redirect()->route('portal.dashboard')
                ->withErrors(['wifi' => 'Please log out and log in again to connect (session credentials expired).']);
        }

        PackageUsage::consolidateActivePurchases($user, touchRouter: false);

        $activePurchase = PackageUsage::activePurchaseForDisplay($user);
        $usageUser = $activePurchase
            ? HotspotIdentity::usageUsernameFor($user, $activePurchase)
            : null;

        if (! $activePurchase || ! PackageUsage::hasDataRemaining($activePurchase, $usageUser)) {
            $activePurchase = $quota->syncForUser($user, force: true);
            $usageUser = $activePurchase
                ? HotspotIdentity::usageUsernameFor($user, $activePurchase)
                : null;

            if (! $activePurchase || ! PackageUsage::hasDataRemaining($activePurchase, $usageUser)) {
                return redirect()->route('portal.packages')
                    ->withErrors(['wifi' => 'Your data is used up or no active plan. Buy a new package to connect.']);
            }
        }

        app(SingleDeviceGuard::class)->disconnectOtherHotspotSessions($user);

        if (HotspotIdentity::usesPerPurchase($activePurchase)) {
            $hotspotPassword = $activePurchase->hotspotLoginPassword();

            if (! $usageUser || ! $hotspotPassword) {
                return redirect()->route('portal.dashboard')
                    ->withErrors(['wifi' => 'Your plan is still activating. Wait a moment and tap Connect again.']);
            }

            return view('portal.auth.hotspot-login', [
                'loginUrl' => config('mikrotik.login_url'),
                'postLoginUrl' => config('mikrotik.post_login_url'),
                'username' => $usageUser,
                'password' => $hotspotPassword,
            ]);
        }

        return view('portal.auth.hotspot-login', [
            'loginUrl' => config('mikrotik.login_url'),
            'postLoginUrl' => config('mikrotik.post_login_url'),
            'username' => $user->phone_number,
            'password' => $password,
        ]);
    }

    protected function finalizePortalLogin(Request $request, User $user, string $password): void
    {
        $guard = app(SingleDeviceGuard::class);
        $guard->disconnectOtherHotspotSessions($user);
        $version = $guard->bindPortalSession($user);
        $request->session()->put('portal_session_version', $version);
        $this->storeWifiPassword($request, $password);
    }

    protected function storeWifiPassword(Request $request, string $password): void
    {
        $request->session()->put('portal_wifi_password', Crypt::encryptString($password));
    }

    protected function wifiPasswordFromSession(Request $request): ?string
    {
        $stored = $request->session()->get('portal_wifi_password');

        if (! is_string($stored) || $stored === '') {
            return null;
        }

        try {
            return Crypt::decryptString($stored);
        } catch (\Throwable) {
            return $stored;
        }
    }
}
