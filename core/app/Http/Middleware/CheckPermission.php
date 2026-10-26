<?php

namespace App\Http\Middleware;

use App\Support\RoleAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Role permissions (config/permissions.php), checked on the server for every page, AJAX call, export and download.
 *   permission                               the keys listed for the route's controller method in the registry
 *   permission:reports.call_report.view      these keys (any one of them is enough)
 * Without permission: JSON 403 for AJAX, otherwise the 403 page. Routes not in the registry pass unchanged.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$keys)
    {
        $user = Auth::user();
        if (!$user) {
            return $next($request);   // signed-out requests are handled by "auth"
        }
        if (!$keys) {
            $route = $request->route();
            $keys = $route ? (RoleAccess::keysForAction($route->getActionName()) ?? []) : [];
        }
        if (!$keys || RoleAccess::allowsAny($user, $keys)) {
            return $next($request);
        }

        $message = 'You do not have permission for this. Ask your administrator to give your role access.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'status' => 'error', 'message' => $message, 'permission' => $keys[0]], 403);
        }
        abort(403, $message);
    }
}
