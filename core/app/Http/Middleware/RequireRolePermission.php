<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Server-side check of the role flags in the permissions table (Users > Permissions):
 *   lg.permission:add    -> permissions.add_status
 *   lg.permission:edit   -> permissions.edit_status
 *   lg.permission:delete -> permissions.delete_status
 * Same outcome as the existing inline checks: JSON 403 for AJAX, otherwise the "No Permission" page.
 */
class RequireRolePermission
{
    private const FLAGS = ['add' => 'add_status', 'edit' => 'edit_status', 'delete' => 'delete_status'];

    public function handle(Request $request, Closure $next, string $action)
    {
        $flag = self::FLAGS[$action] ?? null;
        $group = Auth::user()->permissionsGroup ?? null;

        if (!$flag || !$group || !(int)$group->{$flag}) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'You do not have permission to ' . $action . ' this record.'], 403);
            }
            return redirect()->route('NoPermission');
        }

        return $next($request);
    }
}
