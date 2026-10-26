<?php

namespace App\Support;

use App\Models\Permissions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Role permissions from config/permissions.php: which menus and actions a role may use.
 * A role (row of the permissions table) keeps its keys in role_permissions. Full-access roles (Admin, Webmaster)
 * have every key. A role that was never saved with keys (permissions_managed = 0) keeps the access it had
 * before role permissions existed (legacyKeys), so nothing changes for it.
 */
class RoleAccess
{
    private static array $memo = [];
    private static ?array $keys = null;
    private static ?array $routes = null;

    public static function groups(): array
    {
        return (array)config('permissions.groups', []);
    }

    /** Every key in sidebar order: key => ['group' => , 'item' => , 'action' => , 'admin_only' => bool]. */
    public static function keys(): array
    {
        if (self::$keys !== null) {
            return self::$keys;
        }
        $keys = [];
        foreach (self::groups() as $g => $group) {
            foreach ($group['items'] as $i => $item) {
                foreach ($item['actions'] as $a) {
                    $keys["{$g}.{$i}.{$a}"] = ['group' => $g, 'item' => $i, 'action' => $a,
                        'admin_only' => !empty($item['admin_only']) || in_array($a, $item['admin_only_actions'] ?? [], true)];
                }
            }
        }
        return self::$keys = $keys;
    }

    public static function exists(string $key): bool
    {
        return isset(self::keys()[$key]);
    }

    public static function isAdminOnly(string $key): bool
    {
        return (bool)(self::keys()[$key]['admin_only'] ?? false);
    }

    /** Keys a non-admin role can be given. */
    public static function grantable(): array
    {
        return array_keys(array_filter(self::keys(), fn ($k) => !$k['admin_only']));
    }

    public static function isFullAccessRole($role): bool
    {
        return $role && in_array((string)$role->name, (array)config('permissions.full_access_roles', []), true);
    }

    public static function isFullAccess($user): bool
    {
        return $user && self::isFullAccessRole($user->permissionsGroup);
    }

    /** The keys of a role (cached 10 minutes; cleared when the role is saved or deleted). */
    public static function roleKeys($role): array
    {
        if (!$role) {
            return [];
        }
        if (self::isFullAccessRole($role)) {
            return array_keys(self::keys());
        }
        $memo = config('cache.default') . ':' . $role->id;   // the cache is per company (App\Support\Tenancy)
        return self::$memo[$memo] ??= Cache::remember(self::cacheKey($role->id), (int)config('permissions.cache_seconds', 600), function () use ($role) {
            if (!(int)($role->permissions_managed ?? 0) || !Schema::hasTable('role_permissions')) {
                return self::legacyKeys($role);
            }
            $saved = DB::table('role_permissions')->where('role_id', $role->id)->pluck('permission_key')->all();
            return array_values(array_filter(array_keys(self::keys()), fn ($k) => in_array($k, $saved, true) && !self::isAdminOnly($k)));
        });
    }

    public static function allows($user, string $key): bool
    {
        if (!$user) {
            return false;
        }
        if (self::isFullAccess($user)) {
            return true;
        }
        return !self::isAdminOnly($key) && in_array($key, self::roleKeys($user->permissionsGroup), true);
    }

    public static function allowsAny($user, array $keys): bool
    {
        foreach ($keys as $key) {
            if (self::allows($user, $key)) {
                return true;
            }
        }
        return false;
    }

    /** Any action of an item ("reports.call_report") or of a group ("reports"). */
    public static function allowsAnyIn($user, string $prefix): bool
    {
        foreach (array_keys(self::keys()) as $key) {
            if (str_starts_with($key, $prefix . '.') && self::allows($user, $key)) {
                return true;
            }
        }
        return false;
    }

    /** The keys of the signed-in user, for the page scripts (lgCan). */
    public static function forScript($user): array
    {
        return ['full' => self::isFullAccess($user), 'keys' => $user ? array_values(array_filter(array_keys(self::keys()), fn ($k) => self::allows($user, $k))) : []];
    }

    public static function forgetRole(int $roleId): void
    {
        Cache::forget(self::cacheKey($roleId));
        unset(self::$memo[config('cache.default') . ':' . $roleId]);
    }

    private static function cacheKey(int $roleId): string
    {
        return 'lg_role_perms_' . $roleId;
    }

    /** "Controller@method" (short names, as in the registry) => the keys of which one is needed. */
    public static function routeMap(): array
    {
        if (self::$routes !== null) {
            return self::$routes;
        }
        $map = [];
        foreach (self::groups() as $g => $group) {
            foreach ($group['items'] as $i => $item) {
                foreach ($item['routes'] ?? [] as $action => $need) {
                    $map[$action] = is_array($need) ? $need : ["{$g}.{$i}.{$need}"];
                }
            }
        }
        return self::$routes = $map;
    }

    /** The keys needed for a route action ("App\Http\Controllers\Dashboard\LeadsController@index"), or null when not listed. */
    public static function keysForAction(string $actionName): ?array
    {
        if (!str_contains($actionName, '@')) {
            return null;
        }
        [$class, $method] = explode('@', $actionName, 2);
        $short = preg_replace('/Controller$/', '', class_basename($class));
        return self::routeMap()["{$short}@{$method}"] ?? null;
    }

    /**
     * Checks keys sent for a role. Returns [keys in registry order, errors]: unknown keys, admin-only keys and
     * actions without the item's "view" are errors (the popup never sends them).
     */
    public static function validate(array $keys): array
    {
        $errors = [];
        $keys = array_values(array_unique(array_filter($keys, 'is_string')));
        foreach ($keys as $key) {
            if (!self::exists($key)) {
                $errors[] = "Unknown permission \"{$key}\".";
            } elseif (self::isAdminOnly($key)) {
                $errors[] = "\"{$key}\" is for Admin only and cannot be given to a role.";
            } else {
                $k = self::keys()[$key];
                $view = "{$k['group']}.{$k['item']}.view";
                if ($k['action'] !== 'view' && self::exists($view) && !in_array($view, $keys, true)) {
                    $errors[] = "\"{$key}\" needs \"{$view}\".";
                }
            }
        }
        return [array_values(array_intersect(array_keys(self::keys()), $keys)), $errors];
    }

    /** The registry as the Create Role popup shows it. */
    public static function forPopup(): array
    {
        $actions = (array)config('permissions.actions', []);
        $out = [];
        foreach (self::groups() as $g => $group) {
            $items = [];
            foreach ($group['items'] as $i => $item) {
                $items[] = [
                    'key' => "{$g}.{$i}", 'label' => $item['label'], 'hint' => $item['hint'] ?? null,
                    'admin_only' => !empty($item['admin_only']),
                    'actions' => array_map(fn ($a) => [
                        'key' => "{$g}.{$i}.{$a}", 'action' => $a,
                        'label' => $item['action_labels'][$a] ?? ($actions[$a] ?? ucfirst($a)),
                        'admin_only' => self::isAdminOnly("{$g}.{$i}.{$a}"),
                    ], $item['actions']),
                ];
            }
            $out[] = ['key' => $g, 'label' => $group['label'], 'icon' => $group['icon'] ?? 'folder', 'items' => $items];
        }
        return $out;
    }

    /**
     * What a role could use before role permissions existed, worked out from the checks in the code at that time
     * (role names and the permissions-table flags). Used once to give every existing role its keys, and for roles
     * that have not been saved with keys yet. Full-access roles are not passed here.
     */
    public static function legacyKeys($role): array
    {
        $name = (string)$role->name;
        $flag = fn (string $col, int $default = 0) => (int)($role->{$col} ?? $default) === 1;
        $manager = $name === 'Manager';                                  // the Admin/Manager-only buttons, for a non-full role
        $canDelete = $flag('delete_status');                             // Helper::canDelete() before
        $settings = $flag('settings_status') || (int)$role->id === 1;    // Staff, Call Setup, Profile of others

        $grant = [
            'dashboard.home.view' => true, 'dashboard.home.export' => true,

            'leads.lead_list.view' => true, 'leads.lead_list.edit' => true,
            'leads.lead_list.create' => $name !== 'Auditor',             // "New Lead" was hidden only for Auditor
            'leads.lead_list.export' => $manager,                        // Excel export: Webmaster, Admin, Manager
            'leads.import_leads.view' => true, 'leads.import_leads.create' => true,

            'campaigns.campaign_list.view' => true, 'campaigns.campaign_list.export' => true,
            'campaigns.campaign_list.edit' => in_array($name, ['Manager', 'Team Leader'], true),   // edit icons: Admin, Manager, Team Leader
            'campaigns.campaign_list.delete' => $canDelete,
            'campaigns.create_campaign.create' => true,
            'campaigns.transfer_leads.view' => true, 'campaigns.transfer_leads.edit' => true,
            'campaigns.auto_distribute.view' => true, 'campaigns.auto_distribute.edit' => true,
            'campaigns.add_voucher.view' => true, 'campaigns.add_voucher.create' => true, 'campaigns.add_voucher.edit' => true,
            'campaigns.add_voucher.delete' => $canDelete,

            'calling.dialing.view' => true,

            'configuration.call_setup.view' => $settings, 'configuration.call_setup.edit' => $settings,
            'configuration.profile.view' => true, 'configuration.profile.edit' => true,

            // Products: the Add button followed add_status; the Actions column was Admin/Manager only
            'master_data.products.view' => true, 'master_data.products.create' => $flag('add_status'),
            'master_data.products.edit' => $manager && $flag('edit_status'), 'master_data.products.delete' => $manager && $canDelete,

            'people.staff.view' => $settings, 'people.staff.edit' => $settings,
            'people.staff.create' => $settings && $flag('webmaster_status'),   // "Add Staff" needed webmaster_status, saving settings_status
            'people.staff.delete' => $settings && $canDelete,
            'people.teams.view' => true, 'people.teams.create' => $manager, 'people.teams.edit' => $manager,
            'people.teams.delete' => $manager && $canDelete,
            'people.webrtc.view' => true, 'people.webrtc.create' => true, 'people.webrtc.edit' => true, 'people.webrtc.delete' => $canDelete,
            'people.click2call.view' => true, 'people.click2call.create' => true, 'people.click2call.edit' => true,
            'people.click2call.delete' => $canDelete,

            'reports.call_report.view' => true, 'reports.call_report.export' => true,
            'reports.agent_performance.view' => true, 'reports.agent_performance.export' => $manager,
            'reports.campaigns.view' => true, 'reports.campaigns.export' => true,
            'reports.inbound_calls.view' => $flag('inbound_calls_status', 1), 'reports.inbound_calls.export' => $flag('inbound_calls_status', 1),
            'reports.transcript_report.view' => in_array($name, ['Manager', 'Super Admin'], true),
            'reports.transcript_report.export' => in_array($name, ['Manager', 'Super Admin'], true),

            'recordings.call_recordings.view' => true, 'recordings.call_recordings.play' => true, 'recordings.call_recordings.download' => true,
            'recordings.call_recordings.transcribe' => in_array($name, ['Manager', 'Super Admin'], true),
            'recordings.rmi_analysis.view' => true, 'recordings.rmi_analysis.export' => true,
        ];
        // Sources, Segments, Lead Status: Add / Options were shown to Admin and Manager (the server checked the flags too)
        foreach (['sources', 'segments', 'statuses'] as $item) {
            $grant["master_data.{$item}.view"] = true;
            $grant["master_data.{$item}.create"] = $manager && $flag('add_status');
            $grant["master_data.{$item}.edit"] = $manager && $flag('edit_status');
            $grant["master_data.{$item}.delete"] = $manager && $canDelete;
        }

        return array_values(array_filter(array_keys(self::keys()), fn ($k) => !empty($grant[$k]) && !self::isAdminOnly($k)));
    }

    /** Makes a unique slug for a role name ("Sales Manager" -> "sales_manager"). */
    public static function uniqueSlug(string $name, ?int $exceptId = null): string
    {
        $base = \Illuminate\Support\Str::slug($name, '_') ?: 'role';
        $slug = $base;
        for ($n = 2; Permissions::where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists(); $n++) {
            $slug = $base . '_' . $n;
        }
        return $slug;
    }
}
