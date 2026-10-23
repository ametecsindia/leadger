<?php

/*
 * Multi-company (tenancy): one LeadGER code base, one database per company.
 * The central database (the "mysql" connection, DB_DATABASE) holds the companies list (tenants table) and is also
 * the data of the central company (e.g. app.leadger.in). Every other company is a row in tenants, reached by its own
 * host (company-a.leadger.in) and with its own database. See App\Support\Tenancy and App\Http\Middleware\Tenants.
 */
return [
    // Hosts that use the central database when they are not in the tenants table (comma separated).
    // Default: the host of APP_URL.
    'central_hosts' => array_values(array_filter(array_map('trim', explode(',', (string)env('TENANCY_CENTRAL_HOSTS',
        (string)parse_url((string)env('APP_URL', 'http://localhost'), PHP_URL_HOST)))))),

    // New companies get <slug>.<base_domain> (wildcard DNS *.leadger.in + wildcard SSL on the server)
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'leadger.in'),

    // How long a host -> company lookup is cached (seconds); a status change also clears it at once
    'lookup_cache_seconds' => (int)env('TENANCY_LOOKUP_CACHE_SECONDS', 300),

    // Company databases are created with this prefix (leadger_co_<slug>)
    'database_prefix' => env('TENANCY_DATABASE_PREFIX', 'leadger_co_'),
];
