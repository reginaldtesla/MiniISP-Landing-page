<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PaystackCustomerEmail;
use App\Support\PhoneNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->where('is_admin', false)
            ->latest()
            ->paginate(25);

        return view('admin.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $phone = PhoneNumber::normalize($request->input('phone_number'));

        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! PhoneNumber::isValid($phone)) {
            return back()->withInput()->withErrors([
                'phone_number' => 'Enter a valid Ghana mobile number.',
            ]);
        }

        if (User::query()->where('phone_number', $phone)->exists()) {
            return back()->withInput()->withErrors([
                'phone_number' => 'This phone number is already registered.',
            ]);
        }

        User::setPlainPasswordForRadius($validated['password']);

        User::query()->create([
            'name' => $phone,
            'email' => PaystackCustomerEmail::forPhone($phone),
            'phone_number' => $phone,
            'password' => $validated['password'],
            'device_limit' => (int) config('tesnet.student_device_limit', 1),
            'wallet_balance' => 0,
            'is_admin' => false,
        ]);

        User::setPlainPasswordForRadius(null);

        return redirect()->route('admin.users.index')
            ->with('status', 'Student '.$phone.' created.');
    }

    public function edit(User $user): View
    {
        if ($user->isAdmin()) {
            abort(404);
        }

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'is_suspended' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        if (! empty($validated['password'])) {
            User::setPlainPasswordForRadius($validated['password']);
            $user->password = $validated['password'];
            User::setPlainPasswordForRadius(null);
        }

        $user->device_limit = (int) config('tesnet.student_device_limit', 1);
        $user->is_suspended = (bool) ($validated['is_suspended'] ?? false);
        $user->save();

        return redirect()->route('admin.users.index')
            ->with('status', ! empty($validated['password'])
                ? 'Password updated for '.$user->phone_number.'.'
                : 'User '.$user->phone_number.' updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isAdmin()) {
            return back()->withErrors(['user' => 'Cannot delete an administrator.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'User deleted.');
    }
}
