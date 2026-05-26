<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showForgot(): View
    {
        return view('portal.auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $phone = PhoneNumber::normalize($request->input('phone_number'));

        $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
        ]);

        if (! PhoneNumber::isValid($phone)) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Enter a valid Ghana mobile number.',
            ]);
        }

        $user = User::query()
            ->where('phone_number', $phone)
            ->where('is_admin', false)
            ->first();

        if (! $user) {
            return back()->withInput()->with('status', 'If this number is registered, you can reset your password on the next screen.');
        }

        $request->session()->put('password_reset_user_id', $user->id);
        $request->session()->put('password_reset_phone', $phone);

        return redirect()->route('portal.password.reset')
            ->with('status', 'Set a new password for your account.');
    }

    public function showReset(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_user_id')) {
            return redirect()->route('portal.password.forgot')
                ->withErrors(['phone_number' => 'Start by entering your phone number.']);
        }

        return view('portal.auth.reset-password', [
            'phone' => $request->session()->get('password_reset_phone'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('password_reset_user_id');

        if (! $userId) {
            return redirect()->route('portal.password.forgot')
                ->withErrors(['phone_number' => 'Your reset session expired. Try again.']);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::query()
            ->where('id', $userId)
            ->where('is_admin', false)
            ->first();

        if (! $user) {
            $request->session()->forget(['password_reset_user_id', 'password_reset_phone']);

            return redirect()->route('portal.password.forgot')
                ->withErrors(['phone_number' => 'Account not found.']);
        }

        User::setPlainPasswordForRadius($validated['password']);
        $user->password = $validated['password'];
        $user->save();
        User::setPlainPasswordForRadius(null);

        $request->session()->forget(['password_reset_user_id', 'password_reset_phone']);

        return redirect()->route('portal.login')
            ->with('status', 'Password updated. Sign in with your new password.');
    }
}
