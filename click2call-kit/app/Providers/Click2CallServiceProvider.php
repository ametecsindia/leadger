<?php

namespace App\Providers;

use App\Console\Commands\C2cSweepCommand;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Registers the Click2Call kit: routes, the admin gate and the sweep command.
 * Add to bootstrap/providers.php (Laravel 11+) or config/app.php "providers" (older Laravel).
 */
class Click2CallServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Who may add vouchers, manage numbers and switch Click2Call on for users (adapt isC2cAdmin() in the trait)
        Gate::define('c2c-admin', fn ($user) => method_exists($user, 'isC2cAdmin') && $user->isC2cAdmin());

        Route::group([], base_path('routes/click2call.php'));

        if ($this->app->runningInConsole()) {
            $this->commands([C2cSweepCommand::class]);
        }
    }
}
