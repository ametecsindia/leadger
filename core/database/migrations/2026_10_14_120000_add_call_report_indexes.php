<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Call Report (campaign + manual calls in one query): indexes on the call date, agent and campaign of both call tables.
 * Only indexes that do not exist yet are added.
 */
return new class extends Migration
{
    private const INDEXES = [
        'call_logs' => [
            'idx_call_logs_created_at' => ['created_at'],
            'idx_call_logs_created_by_created_at' => ['created_by', 'created_at'],
            'idx_call_logs_campaign_created_at' => ['campaign_id', 'created_at'],
        ],
        'dialer_logs' => [
            'idx_dialer_logs_created_at' => ['created_at'],
            'idx_dialer_logs_created_by_created_at' => ['created_by', 'created_at'],
            'idx_dialer_logs_campaign_id' => ['campaign_id'],
        ],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;
            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name) || array_diff($columns, Schema::getColumnListing($table))) continue;
                Schema::table($table, fn (Blueprint $t) => $t->index($columns, $name));
            }
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            if (!Schema::hasTable($table)) continue;
            foreach (array_keys($indexes) as $name) {
                if (Schema::hasIndex($table, $name)) {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($name));
                }
            }
        }
    }
};
