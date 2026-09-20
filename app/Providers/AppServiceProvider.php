<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-login', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::perMinute(100)->by($this->emailAndIp($request));
            }

            return Limit::perMinute(5)->by($this->emailAndIp($request));
        });

        RateLimiter::for('password-recovery', function (Request $request) {
            return Limit::perMinute(3)->by($this->emailAndIp($request));
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(5)->by($this->emailAndIp($request));
        });

        RateLimiter::for('email-verification-resend', function (Request $request) {
            $user = $request->user();

            return Limit::perHour(3)->by((string) ($user ? $user->getAuthIdentifier() : $request->ip()));
        });

        RateLimiter::for('gamekit-ai', function (Request $request) {
            $user = $request->user();

            return Limit::perMinute(5)->by((string) ($user ? $user->getAuthIdentifier() : $request->ip()));
        });

        RateLimiter::for('whatsapp-webhook', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });
    }

    private function emailAndIp(Request $request): string
    {
        return Str::lower((string) $request->input('email')).'|'.$request->ip();
    }
}
