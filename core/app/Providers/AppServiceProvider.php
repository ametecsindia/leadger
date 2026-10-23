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

        // Every queued job runs for the company that queued it: the payload carries the company (a host or database
        // name, never credentials); the worker switches to it before the job and back to the previous company after
        // (with the sync driver the job runs inside a request, which must keep its own company).
        $previous = null;
        \Illuminate\Support\Facades\Queue::createPayloadUsing(fn () => ['lg_company' => \App\Support\Tenancy::context()]);
        \Illuminate\Support\Facades\Queue::before(function (\Illuminate\Queue\Events\JobProcessing $event) use (&$previous) {
            $previous = \App\Support\Tenancy::context();
            \App\Support\Tenancy::apply($event->job->payload()['lg_company'] ?? \App\Support\Tenancy::central());
        });
        $restore = function () use (&$previous) {
            if ($previous !== null) {
                [$context, $previous] = [$previous, null];
                \App\Support\Tenancy::apply($context);
            }
        };
        \Illuminate\Support\Facades\Queue::after($restore);
        \Illuminate\Support\Facades\Queue::failing($restore);
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Queue\Events\JobExceptionOccurred::class, $restore);

        // Share call and payout info for scrolling banner
        if (!$this->app->runningInConsole()) {
            URL::forceRootUrl(request()->getSchemeAndHttpHost());

            if (request()->isSecure()) {
                URL::forceScheme('https');
            }
        }
    }
}