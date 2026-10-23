<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * A session belongs to the company it was started in. Sessions of all companies are stored together, and a
 * logged-in session holds only a user id: carried to another company's host (a copied cookie), it would log in as
 * that company's user with the same id. Such a session is discarded and the visitor must log in there.
 * Runs in the web group right after StartSession.
 */
class TenantSession
{
    public function handle($request, Closure $next)
    {
        if ($request->hasSession()) {
            $session = $request->session();
            $company = Tenancy::key();
            $bound = $session->get('lg_company');
            if ($bound !== null && $bound !== $company) {
                Log::warning('[Tenancy] Session of another company refused', ['host' => $request->getHost(), 'ip' => $request->ip()]);
                $session->invalidate();
                $session->regenerateToken();
            }
            $session->put('lg_company', $company);
        }
        return $next($request);
    }
}
