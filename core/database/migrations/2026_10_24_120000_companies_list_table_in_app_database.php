<?php

use App\Services\TenantsCopy;
use App\Support\Tenancy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the companies list lives in another database (TENANCY_DB_DATABASE, e.g. tenants_leadger), this installation's
 * own database (Leadger_app2) gets a real table leadger_tenants with the same 8 columns, filled with the same rows
 * (App\Services\TenantsCopy keeps it in step: every minute and after each change in Configuration > Companies).
 * Removes the view of the same name made by the first version of 2026_10_24_110000, if present.
 * Nothing happens when the list is in this database already.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'mysql' || Tenancy::registry() !== 'tenancy') {
            return;
        }
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string)config('database.connections.mysql.prefix')) . 'tenants';
        $here = (string)config('database.connections.mysql.database');
        $type = DB::connection('mysql')->selectOne(
            'SELECT TABLE_TYPE AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?', [$here, $name])?->t;
        if ($type === 'VIEW') {
            DB::connection('mysql')->statement('DROP VIEW `' . str_replace('`', '', $here) . '`.`' . $name . '`');
        }

        if (!Schema::connection('mysql')->hasTable('tenants')) {
            Schema::connection('mysql')->create('tenants', function (Blueprint $t) {
                $t->id();
                $t->string('subdomain', 191)->unique();
                $t->string('db_host', 191);
                $t->string('db_name', 191);
                $t->string('db_user', 191);
                $t->text('db_password')->nullable();
                $t->timestamps();
            });
        }
        TenantsCopy::sync();
    }

    public function down(): void
    {
        if (DB::getDefaultConnection() !== 'mysql' || Tenancy::registry() !== 'tenancy') {
            return;
        }
        Schema::connection('mysql')->dropIfExists('tenants');   // only the copy; the master list is untouched
    }
};
