<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Poll the provider every minute for connected calls whose recording is still "Processing"
        $schedule->command('recordings:fetch-pending')->everyMinute()->withoutOverlapping();
        // Every 5 minutes also ask about recent calls that never got a recording row (state may be wrong)
        $schedule->command('recordings:fetch-pending --sweep')->everyFiveMinutes()->withoutOverlapping();
        // Nightly: anything from the last 24 hours that still has no recording
        $schedule->command('recordings:fetch-pending --sweep --sweep-minutes=1440')->dailyAt('23:30')->withoutOverlapping();
        // Inbound Calls report: pull the provider's inbound call logs, and nightly link calls to leads / agents added later
        $schedule->command('inbound-calls:pull --hours=3')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('inbound-calls:rematch')->dailyAt('02:15')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
