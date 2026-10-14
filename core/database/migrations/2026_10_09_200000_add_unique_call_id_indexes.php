<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * One call = one row, linked everywhere by its unique call ID.
 *  - call_logs.reference_id / dialer_logs.reference_id: the ID LeadGER sends to the provider as ref_id
 *    (every provider callback is matched on it, so it also needs an index).
 *  - recordings.call_log_id / recordings.dialer_log_id: at most one recording per call.
 * If a tenant database already holds duplicates, a plain index is created instead and the
 * duplicates are logged, so the migration never fails on existing data.
 */
return new class extends Migration
{
    private array $targets = [
        ['call_logs', 'reference_id', 'call_logs_reference_id_unique', 'call_logs_reference_id_index'],
        ['dialer_logs', 'reference_id', 'dialer_logs_reference_id_unique', 'dialer_logs_reference_id_index'],
        ['recordings', 'call_log_id', 'recordings_call_log_id_unique', null],
        ['recordings', 'dialer_log_id', 'recordings_dialer_log_id_unique', null],
    ];

    public function up()
    {
        foreach ($this->targets as [$table, $column, $uniqueName, $plainName]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column) || $this->hasIndex($table, $uniqueName)) {
                continue;
            }

            $duplicates = DB::table($table)->select($column)->whereNotNull($column)
                ->groupBy($column)->havingRaw('COUNT(*) > 1')->limit(20)->pluck($column)->all();

            if (empty($duplicates)) {
                Schema::table($table, fn (Blueprint $t) => $t->unique($column, $uniqueName));
                continue;
            }

            Log::warning("[Migration] {$table}.{$column} has duplicate values, unique index not created", ['examples' => $duplicates]);
            if ($plainName && !$this->hasIndex($table, $plainName)) {
                Schema::table($table, fn (Blueprint $t) => $t->index($column, $plainName));
            }
        }
    }

    public function down()
    {
        foreach ($this->targets as [$table, $column, $uniqueName, $plainName]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if ($this->hasIndex($table, $uniqueName)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropUnique($uniqueName));
            }
            if ($plainName && $this->hasIndex($table, $plainName)) {
                Schema::table($table, fn (Blueprint $t) => $t->dropIndex($plainName));
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        $prefixed = DB::getTablePrefix() . $name;
        foreach (Schema::getIndexes($table) as $index) {
            if (in_array($index['name'], [$name, $prefixed], true)) {
                return true;
            }
        }
        return false;
    }
};
