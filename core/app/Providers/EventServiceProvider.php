<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
           \App\Events\NewIncomingVmnLog::class => [
        \App\Listeners\SendVmnNotification::class,
    ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        // Logging out ends the agent's Auto Dialer session
        Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            if ($event->user) {
                try {
                    \App\Http\Controllers\Dashboard\AutoDialerController::endFor((int)$event->user->id, 'logout');
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[AutoDialer] end on logout failed: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
