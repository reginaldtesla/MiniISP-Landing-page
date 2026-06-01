<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminIdleTimeout;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'studentSignedIn' => $request->user() && ! $request->user()->isAdmin(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        if ($request->user()) {
            if ($request->user()->isAdmin()) {
                return redirect()->route('admin.dashboard');
            }

            return back()->withInput()->withErrors([
                'phone_number' => 'You are already signed in as a student. Open the student portal or log out there first.',
            ]);
        }

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

        if ($account && ! $account->isAdmin()) {
            return back()->withInput()->withErrors([
                'phone_number' => 'This is a student account, not an administrator. Use the student portal sign-in page.',
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Invalid administrator phone number or password.',
            ]);
        }

        $request->session()->regenerate();

        if (! $request->user()->isAdmin()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withInput()->withErrors([
                'phone_number' => 'This account is not an administrator.',
            ]);
        }

        $request->session()->put(AdminIdleTimeout::SESSION_KEY, now()->timestamp);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->forget(AdminIdleTimeout::SESSION_KEY);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
