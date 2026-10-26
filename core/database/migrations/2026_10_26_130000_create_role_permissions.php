<?php

use App\Support\RoleAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role permissions (Configuration > Profile > Roles & Permissions). Roles are the rows of the permissions table.
 *  - permissions: description, slug (unique role key), is_system (Admin / Webmaster: full access, cannot be
 *    edited or deleted), permissions_managed (the role's keys are in role_permissions).
 *  - role_permissions: the keys of each role ("reports.call_report.view", see config/permissions.php).
 *  - role_audit_logs: who created, changed, duplicated or deleted a role, with the keys before and after.
 * Every existing role gets the keys of what it can use today (RoleAccess::legacyKeys), so no access changes.
 * Runs again safely: a role that already has keys is left as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->string('description', 200)->nullable();
            }
            if (!Schema::hasColumn('permissions', 'slug')) {
                $table->string('slug', 60)->nullable()->unique();
            }
            if (!Schema::hasColumn('permissions', 'is_system')) {
                $table->boolean('is_system')->default(false);
            }
            if (!Schema::hasColumn('permissions', 'permissions_managed')) {
                $table->boolean('permissions_managed')->default(false);
            }
        });

        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('permissions')->cascadeOnDelete();
                $table->string('permission_key', 100);
                $table->timestamps();
                $table->unique(['role_id', 'permission_key']);
            });
        }

        if (!Schema::hasTable('role_audit_logs')) {
            Schema::create('role_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('role_id')->nullable()->index();   // kept after the role is deleted
                $table->string('role_name', 100);
                $table->string('action', 20);                                 // created, updated, duplicated, deleted, migrated
                $table->json('before')->nullable();                           // keys (and name / description) before
                $table->json('after')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 191)->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        $now = now();
        foreach (DB::table('permissions')->orderBy('id')->get() as $role) {
            $update = [];
            if (empty($role->slug)) {
                $update['slug'] = RoleAccess::uniqueSlug((string)$role->name, (int)$role->id);
            }
            if (RoleAccess::isFullAccessRole($role)) {
                $update['is_system'] = 1;
            } elseif (!(int)$role->permissions_managed) {
                $keys = RoleAccess::legacyKeys($role);
                DB::table('role_permissions')->insertOrIgnore(array_map(fn ($k) => ['role_id' => $role->id, 'permission_key' => $k,
                    'created_at' => $now, 'updated_at' => $now], $keys));
                $update['permissions_managed'] = 1;
                DB::table('role_audit_logs')->insert(['role_id' => $role->id, 'role_name' => $role->name, 'action' => 'migrated',
                    'after' => json_encode(['keys' => $keys]), 'created_at' => $now]);
            }
            if ($update) {
                DB::table('permissions')->where('id', $role->id)->update($update);
            }
            RoleAccess::forgetRole((int)$role->id);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_audit_logs');
        Schema::dropIfExists('role_permissions');
        Schema::table('permissions', function (Blueprint $table) {
            foreach (['description', 'slug', 'is_system', 'permissions_managed'] as $col) {
                if (Schema::hasColumn('permissions', $col)) {
                    if ($col === 'slug') {
                        $table->dropUnique(['slug']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
