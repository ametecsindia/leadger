<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Encrypt the company database passwords stored in plain text in the tenants table (with APP_KEY).
 * Run it only when nothing else reads that table in plain text (another server, an older copy of the code, scripts),
 * and never change APP_KEY afterwards. App\Models\TenantSetup reads encrypted and plain passwords alike.
 */
class TenantsEncryptPasswordsCommand extends Command
{
    protected $signature = 'tenants:encrypt-passwords {--force : do not ask}';
    protected $description = 'Encrypt plain-text company database passwords in the tenants table';

    public function handle(): int
    {
        $plain = DB::connection('mysql')->table('tenants')->get(['id', 'subdomain', 'db_password'])
            ->filter(function ($row) {
                if ($row->db_password === null || $row->db_password === '') return false;
                try {
                    Crypt::decryptString($row->db_password);
                    return false;   // already encrypted
                } catch (\Throwable $e) {
                    return true;
                }
            });

        if ($plain->isEmpty()) {
            $this->info('All company database passwords are already encrypted.');
            return self::SUCCESS;
        }
        $this->line($plain->count() . ' plain-text password(s): ' . $plain->pluck('subdomain')->implode(', '));
        $this->warn('Afterwards only this application (with this APP_KEY) can read them.');
        if (!$this->option('force') && !$this->confirm('Encrypt them now?')) {
            return self::SUCCESS;
        }
        foreach ($plain as $row) {
            DB::connection('mysql')->table('tenants')->where('id', $row->id)->update(['db_password' => Crypt::encryptString($row->db_password)]);
        }
        $this->info('Encrypted ' . $plain->count() . ' password(s).');
        return self::SUCCESS;
    }
}
