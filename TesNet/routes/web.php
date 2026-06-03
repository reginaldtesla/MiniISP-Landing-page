<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ManualPaymentController as AdminManualPaymentController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\PortalSettingsController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Portal\AuthController;
use App\Http\Controllers\Portal\DashboardController;
use App\Http\Controllers\Portal\DeviceController;
use App\Http\Controllers\Portal\ManualPaymentController;
use App\Http\Controllers\Portal\PaymentController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal/login');

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');

        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');

        Route::view('forgot-password', 'portal.auth.forgot-password')->name('password.forgot');
    });

    Route::post('payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');

    Route::middleware(['auth', 'student', 'portal.single_session'])->group(function () {
        Route::get('payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');

        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/live-usage', [DashboardController::class, 'liveUsage'])
            ->middleware('throttle:120,1')
            ->name('dashboard.live-usage');
        Route::get('about-hotspot', [DashboardController::class, 'aboutHotspot'])->name('about');
        Route::get('support', [DashboardController::class, 'support'])->name('support.index');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('connect-wifi', [AuthController::class, 'connectToWifi'])->name('connect-wifi');

        Route::get('packages', [PaymentController::class, 'packages'])->name('packages');
        Route::post('payments/package', [PaymentController::class, 'initializePackage'])->name('payments.package');
        Route::post('payments/custom', [PaymentController::class, 'initializeCustomData'])->name('payments.custom');
        Route::get('payments/history', [PaymentController::class, 'history'])->name('payments.history');

        Route::get('manual-payments', [ManualPaymentController::class, 'create'])->name('manual-payments.create');
        Route::post('manual-payments', [ManualPaymentController::class, 'store'])->name('manual-payments.store');

        Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
        Route::post('devices/{session}/disconnect', [DeviceController::class, 'disconnect'])->name('devices.disconnect');
    });
});

Route::prefix('admin')->name('admin.')->middleware('admin.ip')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->middleware('throttle:login');

    Route::post('logout', [AdminAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'admin', 'admin.idle'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard', [AdminDashboardController::class, 'index']);
    Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('system-health', [SystemHealthController::class, 'index'])->name('system-health.index');

    Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::post('sessions/{session}/disconnect', [SessionController::class, 'disconnect'])->name('sessions.disconnect');

    Route::get('manual-payments', [AdminManualPaymentController::class, 'index'])->name('manual-payments.index');
    Route::post('manual-payments/{manualPaymentRequest}/approve', [AdminManualPaymentController::class, 'approve'])->name('manual-payments.approve');
    Route::post('manual-payments/{manualPaymentRequest}/reject', [AdminManualPaymentController::class, 'reject'])->name('manual-payments.reject');
    Route::get('manual-payments/{manualPaymentRequest}/proof', [AdminManualPaymentController::class, 'proof'])->name('manual-payments.proof');

    Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
    Route::get('packages/create', [PackageController::class, 'create'])->name('packages.create');
    Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
    Route::get('packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
    Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
    Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [NotificationController::class, 'store'])->name('notifications.store');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('portal-settings', [PortalSettingsController::class, 'edit'])->name('portal-settings.edit');
    Route::put('portal-settings', [PortalSettingsController::class, 'update'])->name('portal-settings.update');

    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::patch('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    });
});
