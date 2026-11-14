<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Events\NewIncomingVmnLog;

class FireIncomingLogEvent extends Command
{
    protected $signature = 'event:fire-incoming-log 
        {database} 
        {username} 
        {password} 
        {host} 
        {log_id}';

    protected $description = 'Fires NewIncomingVmnLog event for a given log ID and DB credentials';

    public function handle()
    {
        $database = $this->argument('database');
        $username = $this->argument('username');
        $password = $this->argument('password');
        $host     = $this->argument('host');
        $logId    = $this->argument('log_id');

        // Dynamically set the DB connection for the tenant
        Config::set('database.connections.tenant', [
            'driver'    => 'mysql',
            'host'      => $host,
            'database'  => $database,
            'username'  => $username,
            'password'  => $password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');

        // Fetch the log record
        $log = DB::connection('tenant')
            ->table('leadger_incoming_vmn_logs')
            ->where('id', $logId)
            ->first();

        if (!$log) {
            $this->error("Log ID {$logId} not found in database: {$database}");
            return 1;
        }

        // Inbound Calls report + bell / popup notifications + Dashboard active calls (the RabbitMQ message is the call's
        // end: answered with talk time, otherwise missed). Never stops the event below.
        $this->recordInbound($database, (array)$log);

        // Trigger the event
        event(new NewIncomingVmnLog((object) $log));

        $this->info("Event fired for Log ID: {$logId} in database: {$database}");
        return 0;
    }

    /** Save the call in inbound_calls of the company whose database is $database (InboundCallService). */
    private function recordInbound(string $database, array $log): void
    {
        try {
            foreach (\App\Support\Tenancy::companies() as $company) {
                \App\Support\Tenancy::apply($company);
                if (DB::connection()->getDatabaseName() === $database) {
                    $r = (new \App\Services\InboundCallService())->record($log + ['event_type' => 'Call End'], 'rabbitmq');
                    $this->line('Inbound call: ' . $r['result'] . ' (' . $r['message'] . ')');
                    return;
                }
            }
            $this->warn("Inbound call not saved: no company uses database {$database}");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[InboundCalls] RabbitMQ incoming log not saved: ' . $e->getMessage());
        } finally {
            \App\Support\Tenancy::apply(\App\Support\Tenancy::central());
        }
    }
}
