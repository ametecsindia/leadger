<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Payout;
use App\Models\CallLog;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();
        Schema::defaultStringLength(191);
    
        // Share call and payout info for scrolling banner
        if (!$this->app->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());

            if (request()->isSecure()) {
                URL::forceScheme('https');
            }
        }
    }
}