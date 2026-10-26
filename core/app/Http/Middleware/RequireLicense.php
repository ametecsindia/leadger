<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Auth\LoginController;
use App\Services\LicenseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A company without a license cannot be used: a signed-in user of that company is signed out (the same steps as
 * Logout) and sent to the login page, which leads to license activation. AJAX gets 401 JSON with the login URL.
 * The license is read from the current company's own database, so other companies are never affected.
 */
class RequireLicense
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || LicenseService::hasLicense()) {
            return $next($request);
        }

        app(LoginController::class)->endSession($request);
        $message = 'This company has no active license. Sign in again after the license is activated.';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'error', 'message' => $message, 'logged_out' => true, 'redirect' => url('/login')], 401);
        }
        return redirect('/login')->with('errorMessage', $message);
    }
}
