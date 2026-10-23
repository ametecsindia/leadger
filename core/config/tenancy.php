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

    // The companies list in another database than DB_DATABASE (connection "tenancy" in config/database.php), e.g.
    // TENANCY_DB_DATABASE=tenants_leadger on app.leadger.in. Empty = the tenants table of DB_DATABASE.
    'database' => env('TENANCY_DB_DATABASE'),

    // The database that keeps a real-table copy of that companies list (App\Services\TenantsCopy), on the same
    // server and login as DB_DATABASE, e.g. TENANCY_COPY_DATABASE=leadger_app. Empty = DB_DATABASE itself.
    'copy_database' => env('TENANCY_COPY_DATABASE'),

    // true = this installation only MANAGES the companies list (Super Admin > Companies): it does not serve the
    // companies' hosts, run their scheduled tasks or migrate their databases - another installation does that.
    'manage_only' => (bool)env('TENANCY_MANAGE_ONLY', false),

    // Who may open Configuration > Companies (company database access!): these login emails (comma separated),
    // and users whose role is named "Super Admin". Only on the central company.
    'super_admin_emails' => array_values(array_filter(array_map(fn ($e) => strtolower(trim($e)),
        explode(',', (string)env('TENANCY_SUPER_ADMIN_EMAILS', ''))))),

    // Store company database passwords encrypted (APP_KEY). Keep false while another installation or script reads
    // the tenants table: it expects plain text.
    'encrypt_passwords' => (bool)env('TENANCY_ENCRYPT_PASSWORDS', false),
];
