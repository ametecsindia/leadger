<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Str;

class SetSessionCookie
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost(); // e.g. t.leadger.in or lt1.leadger.in
        $cookieName = Str::slug($host, '_') . '_session';

        config(['session.cookie' => $cookieName]);
        // Check if session is initialized, otherwise regenerate and initialize


        return $next($request);
    }
}

