<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Central companies list (tenants): until now a hand-made table with only host + database credentials in plain
 * text. Creates it when missing, adds name / slug / status / subscription / provisioning / migration tracking, and
 * encrypts stored database passwords (App\Models\TenantSetup decrypts them).
 *
 * Central database only: tenants:migrate runs every migration on each company database too, where this one does
 * nothing (a company database never holds the companies list).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDefaultConnection() !== 'mysql') {
            return;
        }

        if (!Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $t) {
                $t->id();
                $t->string('subdomain', 191)->unique();   // the host, e.g. company-a.leadger.in
                $t->string('db_host', 191)->default('127.0.0.1');
                $t->string('db_name', 191);
                $t->string('db_user', 191);
                $t->text('db_password')->nullable();      // encrypted
                $t->timestamps();
            });
        }

        Schema::table('tenants', function (Blueprint $t) {
            $add = fn (string $c) => !Schema::hasColumn('tenants', $c);
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

        // encrypted passwords are longer than the old column may allow
        if (Schema::getColumnType('tenants', 'db_password') !== 'text') {
            Schema::table('tenants', fn (Blueprint $t) => $t->text('db_password')->nullable()->change());
        }

        foreach (DB::table('tenants')->get(['id', 'db_password']) as $row) {
            if ($row->db_password === null || $row->db_password === '') continue;
            try {
                Crypt::decryptString($row->db_password);   // already encrypted
            } catch (\Throwable $e) {
                DB::table('tenants')->where('id', $row->id)->update(['db_password' => Crypt::encryptString($row->db_password)]);
            }
        }
    }

    public function down(): void
    {
        // the companies list is never dropped by a rollback; passwords stay encrypted
    }
};
