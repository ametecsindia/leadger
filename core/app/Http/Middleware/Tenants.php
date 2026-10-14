<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\TenantSetup;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use PDO;

class Tenants
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        URL::forceRootUrl($request->getSchemeAndHttpHost());

    // If using HTTPS, force it
        if ($request->isSecure()) {
           URL::forceScheme('https');
        }

        //$subdomain = $request->route()->getParameter('account');
        $subdomain = $request->getHttpHost(); // returns localhost:8099
        // dd($subdomain);

        
        
        // dd(config()->get('database'));
        if(Schema::hasTable('tenants')){
            $tenant = Cache::remember($subdomain.'tenants',24*60,  function() use ($subdomain) {
                return TenantSetup::where('subdomain', $subdomain)->first();
            });
            // $bb=TenantSetup::where('subdomain', 'localhost:8040')->first(); 
            // dd($bb);// returns null

            // dd($tenant->toArray());
            if ($tenant) {
                $this->setTenantDatabase($tenant);



            } elseif(!empty(env($subdomain))) {
                // Connecting to main DB for main application
                $dbname = env($subdomain);
                Config::set("database.connections.mysql.database", $dbname);
                DB::reconnect();
            } elseif(in_array($request->getHost(), ['localhost', '127.0.0.1', '192.168.0.128']) || env('APP_ENV') === 'development' || env('APP_ENV') === 'local' || env('APP_ENV') === 'testing' || app()->environment('testing')) {
                $this->useMainDatabase(env('DB_DATABASE', 'lt_leadger'));
            } else {
                // If tenant is not found, you can handle this case
                abort(404, "Tenant not found");
            }
        } else {
            $dbname = env($subdomain) ?: env('DB_DATABASE', 'lt_leadger');
            if ($dbname) {
                $this->useMainDatabase($dbname);
            }
        }
        try {
            $settings = DB::table('settings')->first();

            if ($settings && $settings->session_timeout) {
                Config::set('session.lifetime', $settings->session_timeout);
            }
        } catch (\Exception $e) {
            // Optional: log the error or handle fallback value
            Config::set('session.lifetime', 120); // default fallback
        }

        return $next($request);

    }

    /**
     * Point the default "mysql" connection at $dbname. Reconnect ONLY when it is a different database:
     * dropping a healthy connection to the same database discards any open transaction on it
     * (this made test rollbacks silently commit) and costs a reconnect on every request.
     */
    protected function useMainDatabase(string $dbname): void
    {
        \App\Support\Tenancy::useMainDatabase($dbname);
    }

    protected function setTenantDatabase(TenantSetup $tenant)
    {
        // same connection setup as queued jobs use for this company (App\Support\Tenancy)
        \App\Support\Tenancy::useTenant($tenant);

        // Test the connection
        try {
            DB::connection()->getPdo();
            \Log::info("✅ Connected to tenant DB: " . DB::connection()->getDatabaseName());
        } catch (\Exception $e) {
            \Log::error("❌ Tenant DB connection failed: " . $e->getMessage());
        }
    }
    
}
