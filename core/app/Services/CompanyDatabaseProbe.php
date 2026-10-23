<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Looks into one company's database without switching the current request: can it be reached, how many users /
 * leads / calls it has, when the last call was, and how many of this code's migrations it has not run yet.
 * Read-only. Used by Configuration > Companies.
 */
class CompanyDatabaseProbe
{
    private const CONNECTION = 'company_probe';

    /** @param array $db host, database, username, password */
    public static function probe(array $db, bool $withStats = true): array
    {
        config(['database.connections.' . self::CONNECTION => [
            'driver' => 'mysql',
            'host' => $db['host'],
            'port' => env('DB_PORT', 3306),
            'database' => $db['database'],
            'username' => $db['username'],
            'password' => $db['password'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => (string)config('database.connections.mysql.prefix'),
            'strict' => false,
            'options' => [PDO::ATTR_TIMEOUT => 5],
        ]]);
        DB::purge(self::CONNECTION);

        try {
            $c = DB::connection(self::CONNECTION);
            $c->getPdo();
            $result = ['ok' => true, 'error' => null];
            if ($withStats) {
                $schema = $c->getSchemaBuilder();
                $count = fn (string $table) => $schema->hasTable($table) ? (int)$c->table($table)->count() : null;
                $result += [
                    'users' => $count('users'),
                    'leads' => $count('leads'),
                    'calls' => ($count('call_logs') ?? 0) + ($count('dialer_logs') ?? 0),
                    'last_call' => $schema->hasTable('call_logs') ? $c->table('call_logs')->max('created_at') : null,
                    'pending_migrations' => self::pendingMigrations($c),
                ];
            }
            return $result;
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => self::shortError($e->getMessage())];
        } finally {
            DB::purge(self::CONNECTION);
        }
    }

    /** Migration files of this code that the company database has not run (null = no migrations table). */
    private static function pendingMigrations($c): ?int
    {
        if (!$c->getSchemaBuilder()->hasTable('migrations')) {
            return null;
        }
        $ran = $c->table('migrations')->pluck('migration')->flip();
        $files = collect(glob(database_path('migrations/*.php')))->map(fn ($f) => basename($f, '.php'));
        return $files->reject(fn ($m) => isset($ran[$m]))->count();
    }

    /** A connection error without the password or a stack trace. */
    private static function shortError(string $message): string
    {
        if (preg_match('/SQLSTATE\[(\w+)\].*?\] (.*?)( \(Connection:|$)/s', $message, $m)) {
            $message = $m[2];
        }
        return mb_substr(trim($message), 0, 160);
    }
}
