<?php

namespace App\Console\Commands;

use App\Support\Tenancy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Run an artisan command once for every active company (the central one first), each with its own database, cache
 * and folders. Scheduled tasks use it so every company gets its recording fetch, sweeps and nightly jobs:
 *   php artisan tenants:each "recordings:fetch-pending --sweep"
 *   php artisan tenants:each "inbound-calls:rematch" --company=company-a.leadger.in
 */
class TenantsEachCommand extends Command
{
    protected $signature = 'tenants:each {task : the command with its options, in quotes} {--company=* : only these hosts / databases}';
    protected $description = 'Run a command for every active company';

    public function handle(): int
    {
        $task = trim((string)$this->argument('task'));
        if ($task === '' || str_starts_with($task, 'tenants:')) {
            $this->error('Give the command to run, e.g. tenants:each "recordings:fetch-pending"');
            return self::INVALID;
        }
        $only = (array)$this->option('company');
        $failed = 0;

        $results = Tenancy::forEachCompany(function (array $company, string $label) use ($task, $only) {
            if ($only && !in_array($label, $only, true)) {
                return null;
            }
            $code = Artisan::call($task);
            $this->line("<info>{$label}</info>  {$task}  exit {$code}");
            $out = trim(Artisan::output());
            if ($out !== '') $this->line($out);
            return $code;
        });

        foreach ($results as $label => $result) {
            if ($result instanceof \Throwable || (is_int($result) && $result !== 0)) {
                $failed++;
                $message = $result instanceof \Throwable ? $result->getMessage() : 'exit ' . $result;
                Log::error('[Tenancy] Task failed for a company', ['company' => $label, 'task' => $task, 'error' => $message]);
                $this->error("{$label}: {$message}");
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
