<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Permissions;
use App\Models\User;
use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Roles & Permissions (Configuration > Profile): list, show, create, change, duplicate and delete roles with their
 * permission keys (config/permissions.php). Admin only: the routes need configuration.roles_permissions.*, which
 * only full-access roles have; checked again here. Roles are the rows of the permissions table.
 */
class RolesController extends Controller
{
    public function index()
    {
        $this->denyUnlessAdmin();
        $users = User::select('permissions_id', DB::raw('COUNT(*) AS n'))->groupBy('permissions_id')->pluck('n', 'permissions_id');
        $roles = Permissions::orderBy('id')->get()->map(fn ($r) => $this->row($r, (int)($users[$r->id] ?? 0)));
        return response()->json(['status' => 'success', 'roles' => $roles, 'total_permissions' => count(RoleAccess::grantable())]);
    }

    public function show($id)
    {
        $this->denyUnlessAdmin();
        $role = Permissions::findOrFail($id);
        return response()->json(['status' => 'success',
            'role' => $this->row($role, User::where('permissions_id', $role->id)->count()) + ['permissions' => RoleAccess::roleKeys($role)]]);
    }

    public function store(Request $request)
    {
        $this->denyUnlessAdmin();
        [$data, $keys] = $this->validated($request, null);
        $source = $request->filled('duplicate_of') ? Permissions::find((int)$request->duplicate_of) : null;

        $role = DB::transaction(function () use ($data, $keys, $source) {
            $role = new Permissions();
            // the older flags still used by pages outside the permission list: as a role made with "Add New Role"
            // before, or the same as the role it is a copy of
            foreach (['view_status' => 1, 'add_status' => 1, 'edit_status' => 1, 'delete_status' => 0, 'active_status' => 1] as $col => $default) {
                $role->{$col} = $source && !RoleAccess::isFullAccessRole($source) ? $source->{$col} : $default;
            }
            $role->status = 1;
            $role->name = $data['name'];
            $role->description = $data['description'];
            $role->slug = RoleAccess::uniqueSlug($data['name']);
            $role->is_system = 0;
            $role->permissions_managed = 1;
            $role->created_by = Auth::id();
            $role->save();
            $this->saveKeys($role, $keys);
            return $role;
        });

        $this->audit($role, $source ? 'duplicated' : 'created', null, $this->snapshot($role, $keys),
            $source ? ['from_role_id' => $source->id, 'from_role_name' => $source->name] : []);
        return response()->json(['status' => 'success', 'message' => 'Role "' . $role->name . '" created.',
            'role' => $this->row($role->fresh(), 0)], 201);
    }

    public function update(Request $request, $id)
    {
        $this->denyUnlessAdmin();
        $role = Permissions::findOrFail($id);
        if ($this->isSystem($role)) {
            return $this->fail('Admin has full access. This role cannot be changed.', 422);
        }
        [$data, $keys] = $this->validated($request, $role->id);
        $before = $this->snapshot($role, RoleAccess::roleKeys($role));

        DB::transaction(function () use ($role, $data, $keys) {
            if ($role->name !== $data['name']) {
                $role->slug = RoleAccess::uniqueSlug($data['name'], $role->id);
            }
            $role->name = $data['name'];
            $role->description = $data['description'];
            $role->permissions_managed = 1;
            $role->updated_by = Auth::id();
            $role->save();
            $this->saveKeys($role, $keys);
        });

        $this->audit($role, 'updated', $before, $this->snapshot($role, $keys));
        return response()->json(['status' => 'success', 'message' => 'Role "' . $role->name . '" saved.',
            'role' => $this->row($role->fresh(), User::where('permissions_id', $role->id)->count())]);
    }

    public function destroy($id)
    {
        $this->denyUnlessAdmin();
        $role = Permissions::findOrFail($id);
        if ($this->isSystem($role) || in_array((int)$role->id, [1, 4], true)) {
            return $this->fail('System role "' . $role->name . '" cannot be deleted.', 422);
        }
        $users = User::where('permissions_id', $role->id)->count();
        if ($users > 0) {
            return $this->fail("Reassign these {$users} user(s) first. Role \"{$role->name}\" is assigned to {$users} user(s).", 422);
        }
        $before = $this->snapshot($role, RoleAccess::roleKeys($role));
        $role->delete();   // its keys go with it (foreign key, cascade)
        RoleAccess::forgetRole((int)$id);
        $this->audit($role, 'deleted', $before, null);
        return response()->json(['status' => 'success', 'message' => 'Role "' . $role->name . '" deleted.', 'id' => (int)$id]);
    }

    /** @return array{0: array{name: string, description: ?string}, 1: string[]} */
    private function validated(Request $request, ?int $exceptId): array
    {
        $name = preg_replace('/\s+/', ' ', trim((string)$request->input('name')));
        $description = trim((string)$request->input('description')) ?: null;
        $request->merge(['name' => $name, 'description' => $description]);

        $v = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[\pL\pN][\pL\pN .&()\/_-]*$/u'],
            'description' => ['nullable', 'string', 'max:200'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'max:100'],
            'duplicate_of' => ['nullable', 'integer', 'exists:permissions,id'],
        ], [
            'name.required' => 'Enter a role name.',
            'name.min' => 'The role name needs at least 2 characters.',
            'name.max' => 'The role name can have at most 50 characters.',
            'name.regex' => 'Use letters, numbers, spaces and . & ( ) / _ - only.',
            'description.max' => 'The description can have at most 200 characters.',
            'permissions.present' => 'Choose the permissions of this role.',
        ]);
        $v->after(function ($v) use ($name, $exceptId, $request) {
            if ($name === '') {
                return;
            }
            $taken = Permissions::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists();
            $reserved = in_array(mb_strtolower($name), array_map('mb_strtolower', (array)config('permissions.full_access_roles', [])), true);
            if ($taken || $reserved) {
                $v->errors()->add('name', 'A role named "' . $name . '" already exists. Choose another name.');
            }
            [, $errors] = RoleAccess::validate((array)$request->input('permissions', []));
            foreach ($errors as $e) {
                $v->errors()->add('permissions', $e);
            }
        });
        if ($v->fails()) {
            abort(response()->json(['status' => 'error', 'message' => $v->errors()->first(), 'errors' => $v->errors()], 422));
        }
        [$keys] = RoleAccess::validate((array)$request->input('permissions', []));
        return [['name' => $name, 'description' => $description], $keys];
    }

    private function saveKeys(Permissions $role, array $keys): void
    {
        DB::table('role_permissions')->where('role_id', $role->id)->whereNotIn('permission_key', $keys ?: [''])->delete();
        $now = now();
        DB::table('role_permissions')->insertOrIgnore(array_map(fn ($k) => ['role_id' => $role->id, 'permission_key' => $k,
            'created_at' => $now, 'updated_at' => $now], $keys));
        RoleAccess::forgetRole((int)$role->id);   // its users get the new access on their next request
    }

    private function row(Permissions $r, int $users): array
    {
        $system = $this->isSystem($r);
        return [
            'id' => $r->id, 'name' => $r->name, 'slug' => $r->slug ?: \Illuminate\Support\Str::slug($r->name, '_'),
            'description' => $r->description, 'is_system' => $system, 'deletable' => !$system && !in_array((int)$r->id, [1, 4], true),
            'users_count' => $users,
            'permissions_count' => $system ? count(RoleAccess::keys()) : count(RoleAccess::roleKeys($r)),
            'created_at' => $r->created_at ? $r->created_at->format('Y-m-d H:i') : 'System',
        ];
    }

    private function isSystem(Permissions $role): bool
    {
        return (bool)$role->is_system || RoleAccess::isFullAccessRole($role);
    }

    private function snapshot(Permissions $role, array $keys): array
    {
        return ['name' => $role->name, 'description' => $role->description, 'keys' => array_values($keys)];
    }

    private function audit(Permissions $role, string $action, ?array $before, ?array $after, array $extra = []): void
    {
        DB::table('role_audit_logs')->insert([
            'role_id' => $role->id, 'role_name' => $role->name, 'action' => $action,
            'before' => $before ? json_encode($before) : null, 'after' => $after || $extra ? json_encode(($after ?? []) + $extra) : null,
            'user_id' => Auth::id(), 'user_name' => Auth::user()->name ?? null, 'ip' => request()->ip(), 'created_at' => now(),
        ]);
        \Log::info("[Roles] {$action} role #{$role->id} \"{$role->name}\" by user #" . Auth::id());
    }

    private function denyUnlessAdmin(): void
    {
        if (!RoleAccess::isFullAccess(Auth::user())) {
            abort(response()->json(['status' => 'error', 'message' => 'Only the Admin can manage roles.'], 403));
        }
    }

    private function fail(string $message, int $code)
    {
        return response()->json(['status' => 'error', 'message' => $message], $code);
    }
}
