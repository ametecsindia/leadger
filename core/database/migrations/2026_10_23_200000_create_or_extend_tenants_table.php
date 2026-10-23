<?php

use App\Support\Tenancy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Companies list (tenants): creates the table when it is missing, with the 8 columns of the existing live table
 * (id, subdomain, db_host, db_name, db_user, db_password, created_at, updated_at). An existing table is left as it is.
 * (A first version of this migration also added name / slug / status / ... - removed again by
 * 2026_10_24_100000_restore_tenants_table_to_eight_columns.)
 *
 * Works on the companies list's own connection (Tenancy::registry(): tenants_leadger when TENANCY_DB_DATABASE is
 * set, else this installation's database). Skipped while tenants:migrate runs it on a company database.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'mysql') {
            return;
        }
        $schema = Schema::connection(Tenancy::registry());
        if ($schema->hasTable('tenants')) {
            return;
        }
        $schema->create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('subdomain', 191)->unique();   // the host, e.g. company-a.leadger.in
            $t->string('db_host', 191);
            $t->string('db_name', 191);
            $t->string('db_user', 191);
            $t->text('db_password')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        // the companies list is never dropped by a rollback
    }
};
