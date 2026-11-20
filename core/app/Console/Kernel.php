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
        // Light on the shared server: only On Call Now runs every minute; the rest every 2-10 minutes, and nothing
        // runs while the same job is still running (withoutOverlapping)
        // Poll the provider every minute for connected calls whose recording is still "Processing"
        $schedule->command('tenants:each "recordings:fetch-pending"')->everyTwoMinutes()->withoutOverlapping(10);   // every company
        // Every 5 minutes also ask about recent calls that never got a recording row (state may be wrong)
        $schedule->command('tenants:each "recordings:fetch-pending --sweep"')->everyFifteenMinutes()->withoutOverlapping(20);
        // Nightly: anything from the last 24 hours that still has no recording
        $schedule->command('tenants:each "recordings:fetch-pending --sweep --sweep-minutes=1440"')->dailyAt('23:30')->withoutOverlapping(120);
        // Dashboard "On Call Now": close active calls without heartbeat / provider event (no ghost calls)
        $schedule->command('tenants:each "active-calls:cleanup"')->everyMinute()->withoutOverlapping(5);
        // login sessions left open (browser closed / session expired): logged out at their last activity
        $schedule->command('tenants:each "user-logs:close-stale"')->everyFiveMinutes()->withoutOverlapping(5);   // the report and dashboard also close them when opened
        // Copy new call recordings into the Google Cloud Storage bucket (nothing happens until GCS_* is set in .env)
        $schedule->command('tenants:each "recordings:to-bucket"')->everyTenMinutes()->withoutOverlapping(60);
        // Inbound Calls report: pull the provider's inbound call logs, and nightly link calls to leads / agents added later
        $schedule->command('inbound-calls:pull --hours=3')->everyThreeMinutes()->withoutOverlapping(10);   // opening the report also pulls; a crashed run frees the lock after 10 min
        // copy of the companies list in TENANCY_COPY_DATABASE (master: TENANCY_DB_DATABASE) - nothing to do without one
        $schedule->command('tenants:sync-copy')->everyTenMinutes()->withoutOverlapping(5);
        // a site added to the companies list (SQLyog / Configuration > Companies) gets its database ready within a minute
        $schedule->command('tenants:sync-sites')->everyTenMinutes()->withoutOverlapping(10);
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
