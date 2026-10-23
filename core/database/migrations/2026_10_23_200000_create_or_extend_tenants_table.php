<?php

use App\Support\Tenancy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Companies list (tenants): until now a hand-made table with only host + database credentials. Creates it when
 * missing and adds name / slug / status / subscription / provisioning / migration tracking. Existing rows keep their
 * values; all companies start as "active". Passwords are never rewritten here (another installation may read them in
 * plain text; tenants:encrypt-passwords encrypts on purpose).
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

        if (!$schema->hasTable('tenants')) {
            $schema->create('tenants', function (Blueprint $t) {
                $t->id();
                $t->string('subdomain', 191)->unique();   // the host, e.g. company-a.leadger.in
                $t->string('db_host', 191)->default('127.0.0.1');
                $t->string('db_name', 191);
                $t->string('db_user', 191);
                $t->text('db_password')->nullable();
                $t->timestamps();
            });
        }

        $schema->table('tenants', function (Blueprint $t) use ($schema) {
            $add = fn (string $c) => !$schema->hasColumn('tenants', $c);
            if ($add('name')) $t->string('name', 191)->nullable()->after('id');
            if ($add('slug')) $t->string('slug', 64)->nullable()->unique()->after('name');
            if ($add('status')) $t->string('status', 20)->default('active')->index();   // active | suspended | provisioning | failed
            if ($add('plan')) $t->string('plan', 64)->nullable();
            if ($add('max_users')) $t->unsignedInteger('max_users')->nullable();
            if ($add('expires_at')) $t->date('expires_at')->nullable();
            if ($add('provisioning_status')) $t->string('provisioning_status', 30)->nullable();
            if ($add('provisioning_log')) $t->text('provisioning_log')->nullable();
            if ($add('app_version')) $t->string('app_version', 40)->nullable();
            if ($add('last_migrated_at')) $t->dateTime('last_migrated_at')->nullable();
            if ($add('last_migration_status')) $t->string('last_migration_status', 20)->nullable();
            if ($add('last_migration_error')) $t->text('last_migration_error')->nullable();
        });

        // room for an encrypted password (only lengthens the column)
        if (!in_array($schema->getColumnType('tenants', 'db_password'), ['text', 'mediumtext', 'longtext'], true)) {
            $schema->table('tenants', fn (Blueprint $t) => $t->text('db_password')->nullable()->change());
        }
    }

    public function down(): void
    {
        // the companies list is never dropped by a rollback
    }
};
