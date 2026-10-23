<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * Which company this request belongs to, by host (never by anything the browser sends):
 *  - a host in the tenants table -> that company's own database;
 *  - a host mapped in .env (legacy: HOST=database name) -> that database;
 *  - a central host (TENANCY_CENTRAL_HOSTS / APP_URL host), local development, or no tenants table -> the central
 *    database;
 *  - any other host -> 404. Never another company's database.
 */
class Tenants
{
    private const LOCAL_HOSTS = ['localhost', '127.0.0.1', '192.168.0.128'];

    public function handle($request, Closure $next)
    {
        URL::forceRootUrl($request->getSchemeAndHttpHost());
        if ($request->isSecure()) {
            URL::forceScheme('https');
        }

        $host = strtolower($request->getHttpHost());   // with the port, as stored in tenants.subdomain
        $tenant = Tenancy::findTenant($host);

        if ($tenant) {
            Tenancy::useTenant($tenant);
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                \Log::error('[Tenancy] Company database connection failed', ['host' => $host, 'error' => $e->getMessage()]);
                abort(503, 'This company is temporarily unavailable. Please try again shortly.');
            }
        } elseif (!empty(env($host))) {
            Tenancy::useMainDatabase((string)env($host));   // legacy .env host mapping
        } elseif (!Tenancy::hasTenantsTable() || Tenancy::isCentralHost($host) || $this->isLocal($request)) {
            Tenancy::useMainDatabase(Tenancy::central()['database']);
        } else {
            abort(404, 'Company not found');
        }

        try {
            $settings = DB::table('settings')->first();
            if ($settings && $settings->session_timeout) {
                Config::set('session.lifetime', $settings->session_timeout);
            }
        } catch (\Exception $e) {
            Config::set('session.lifetime', 120);
        }

        return $next($request);
    }

    private function isLocal($request): bool
    {
        return in_array($request->getHost(), self::LOCAL_HOSTS, true) || app()->environment(['local', 'development', 'testing']);
    }
}
