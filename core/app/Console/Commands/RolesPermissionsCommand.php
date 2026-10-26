<?php

namespace App\Console\Commands;

use App\Models\Permissions;
use App\Support\RoleAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Shows what every role of this company's database can use (config/permissions.php). Read only.
 * Before the role permissions migration it shows the keys each role will get (its access of today);
 * afterwards the saved keys. For every company: php artisan tenants:each "roles:permissions"
 */
class RolesPermissionsCommand extends Command
{
    protected $signature = 'roles:permissions {--role= : Only this role (id or name)} {--keys : List the granted keys too}';
    protected $description = 'Show the permissions of each role (read only)';

    public function handle(): int
    {
        $migrated = Schema::hasTable('role_permissions') && Schema::hasColumn('permissions', 'permissions_managed');
        $this->line($migrated ? 'Saved role permissions:' : 'Not migrated yet. Keys each role WILL get (its access today):');
        $roles = Permissions::orderBy('id')->get()->filter(fn ($r) => !$this->option('role')
            || (string)$r->id === $this->option('role') || strcasecmp($r->name, (string)$this->option('role')) === 0);
        $grantable = RoleAccess::grantable();

        foreach ($roles as $role) {
            $users = \App\Models\User::where('permissions_id', $role->id)->count();
            if (RoleAccess::isFullAccessRole($role)) {
                $this->info("#{$role->id} {$role->name} ({$users} users): FULL ACCESS, every permission (" . count(RoleAccess::keys()) . ')');
                continue;
            }
            // before the migration: what the migration will save; afterwards: what is saved (or today's access if never saved)
            // never through the cache: the cache files belong to the web server user
            $keys = $migrated ? RoleAccess::savedKeys($role) : RoleAccess::legacyKeys($role);
            $this->info("#{$role->id} {$role->name} ({$users} users): " . count($keys) . ' of ' . count($grantable) . ' permissions');
            if ($this->option('keys')) {
                $this->line('   granted: ' . implode(', ', $keys));
            }
            $this->line('   not granted: ' . (implode(', ', array_diff($grantable, $keys)) ?: '-'));
        }
        return self::SUCCESS;
    }
}
