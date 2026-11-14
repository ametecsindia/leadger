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
        $schedule->command('tenants:each "recordings:fetch-pending"')->everyMinute()->withoutOverlapping(10);   // every company
        // Every 5 minutes also ask about recent calls that never got a recording row (state may be wrong)
        $schedule->command('tenants:each "recordings:fetch-pending --sweep"')->everyFiveMinutes()->withoutOverlapping(20);
        // Nightly: anything from the last 24 hours that still has no recording
        $schedule->command('tenants:each "recordings:fetch-pending --sweep --sweep-minutes=1440"')->dailyAt('23:30')->withoutOverlapping(120);
        // Dashboard "On Call Now": close active calls without heartbeat / provider event (no ghost calls)
        $schedule->command('tenants:each "active-calls:cleanup"')->everyMinute()->withoutOverlapping(5);
        // Copy new call recordings into the Google Cloud Storage bucket (nothing happens until GCS_* is set in .env)
        $schedule->command('tenants:each "recordings:to-bucket"')->everyFiveMinutes()->withoutOverlapping(60);
        // Inbound Calls report: pull the provider's inbound call logs, and nightly link calls to leads / agents added later
        $schedule->command('inbound-calls:pull --hours=3')->everyMinute()->withoutOverlapping(10);   // new calls within a minute; a crashed run frees the lock after 10 min
        // copy of the companies list in TENANCY_COPY_DATABASE (master: TENANCY_DB_DATABASE) - nothing to do without one
        $schedule->command('tenants:sync-copy')->everyMinute()->withoutOverlapping(5);
        // a site added to the companies list (SQLyog / Configuration > Companies) gets its database ready within a minute
        $schedule->command('tenants:sync-sites')->everyMinute()->withoutOverlapping(10);
        $schedule->command('tenants:each "inbound-calls:rematch"')->dailyAt('02:15')->withoutOverlapping(120);
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
