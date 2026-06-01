<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use App\Models\RadAcct;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }

        if (
            ! app()->runningInConsole()
            && ! \App\Support\PortalAssets::bundleReady()
            && config('portal.use_offline_assets', true)
        ) {
            logger()->warning('Portal offline asset bundle missing. Run: npm run build:offline');
        }

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(config('tesnet.login_rate_limit', 10))
                ->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(config('tesnet.register_rate_limit', 5))
                ->by($request->ip());
        });

        Route::bind('session', function (string $value) {
            return RadAcct::query()->where('radacctid', $value)->firstOrFail();
        });
    }
}
