<?php

/*
|--------------------------------------------------------------------------
| Click2Call kit (ported from LeadGER)
|--------------------------------------------------------------------------
| Every URL, secret and limit lives here and in .env - never in code.
| Values marked "LeadGER default" are what LeadGER uses today.
*/

return [

    // ---- Ametecs license server: Add Voucher (VMN numbers) -------------------------------------------
    // GET {license_url}/api/did_voucher/{code}/{crm_id}       -> validate a voucher, returns its numbers
    // GET {license_url}/api/did_voucher_used/{code}/{ip}      -> mark the voucher as used
    'license_url'    => env('C2C_LICENSE_URL', 'https://lc.ametecsindia.com'),
    'license_secret' => env('C2C_LICENSE_SECRET'),          // "Secret-Key" header (from Ametecs)
    'crm_id'         => env('C2C_CRM_ID'),                  // your CRM / license id at Ametecs

    // ---- Calling provider ----------------------------------------------------------------------------
    // Start a call (Telozar). LeadGER sends no credentials: access is controlled by the provider (server IP).
    'start_url'      => env('C2C_START_URL', 'https://sip.telozar.com/api/webrtc/c2c_call'),
    // Call record + recording URL (Ametecs OBD gateway): GET {cdr_url}/{ref_id}/{vmn}, header Secret-Key
    'cdr_url'        => env('C2C_CDR_URL', 'https://obd.ametecsindia.com/admin/click2call_data'),
    'gateway_secret' => env('C2C_GATEWAY_SECRET'),
    'timeout'        => (int) env('C2C_TIMEOUT', 15),       // LeadGER sets none - always set one

    // ---- Webhook: provider -> your app ---------------------------------------------------------------
    'webhook_secret' => env('C2C_WEBHOOK_SECRET'),          // REQUIRED (LeadGER accepts requests without it)
    'webhook_ips'    => array_values(array_filter(array_map('trim', explode(',', (string) env('C2C_WEBHOOK_IPS', ''))))),

    // ---- Calling rules (LeadGER: settings.calling_hours_from/to, settings.daily_call_limit) -----------
    'timezone'       => env('C2C_TIMEZONE', 'Asia/Kolkata'),
    'hours_from'     => env('C2C_CALLING_HOURS_FROM', '09:30'), // LeadGER default
    'hours_to'       => env('C2C_CALLING_HOURS_TO', '19:30'),   // LeadGER default
    'daily_limit'    => (int) env('C2C_DAILY_LIMIT_PER_NUMBER', 100), // LeadGER default
    'agent_lock_seconds' => 6,                                  // LeadGER: one call start per agent at a time

    // Click2Call users allowed (LeadGER: license c2c_channels). 0 = no limit.
    'max_users'      => (int) env('C2C_MAX_USERS', 0),

    // ---- Browser polling / recordings ----------------------------------------------------------------
    'poll_seconds'   => 2,          // LeadGER: the page asks for the call status every 2 s
    'poll_max_minutes' => 15,       // and gives up after 15 minutes
    'recording_disk' => env('C2C_RECORDING_DISK', 'local'),    // a PRIVATE disk
    'recording_wait_minutes' => 60, // no recording after this long -> "unavailable" (LeadGER: 60)

    // ---- Teams (VMNs are assigned to teams) ----------------------------------------------------------
    // An Eloquent model with "id" and "name" (LeadGER: App\Models\Team). If it does not exist, team ids are typed in.
    'team_model'     => env('C2C_TEAM_MODEL', 'App\\Models\\Team'),

    // ---- Views ---------------------------------------------------------------------------------------
    // Blade layout the kit pages extend; it must @yield('content') and @stack('scripts').
    'layout'         => env('C2C_LAYOUT', 'click2call.layout'),
];
