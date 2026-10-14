<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\APIs\CronsController;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Closure;
use Illuminate\Http\Request;

class TelecallerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        //app(CronsController::class)->fetchIncomingVmnLogs(); 
        
       
        $user = Auth::user();

        if ($user) {
            // Fetch user role
            $userRole = DB::table('users')
                ->leftJoin('permissions', 'users.permissions_id', '=', 'permissions.id')
                ->where('users.id', $user->id)
                ->value('permissions.name');

            // Fetch login/logout times from settings
            $settings = DB::table('settings')->first();
            $loginTime = $settings->caller_login ?? '10:30:00';
            $logoutTime = $settings->caller_logout ?? '17:30:00';

            $currentTime = Carbon::now()->format('H:i:s');



            if ($userRole == 'Telecaller') {
                if ($currentTime < $loginTime || $currentTime > $logoutTime) {
                    Auth::logout();
                    return redirect('/login')->withErrors(['error' => 'You can only log in between ' . $loginTime . ' and ' . $logoutTime . '.']);
                }
            }
        }

        return $next($request);
    }
}
