<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserLogs;
use Illuminate\Support\Carbon;


class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/admin';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function credentials(Request $request)
    {
        // clear old sessions
        \Session()->forget('_Loader_WebmasterSettings');
        \Session()->forget('_Loader_Web_Settings');
        \Session()->forget('_Loader_Languages');
        \Session()->forget('_Loader_Events');
        \Session()->forget('_Loader_WebmasterSections');

        return array_merge($request->only($this->username(), 'password'), ['status' => 1]);
    }
    
    public function customLogin(Request $request)
    {
        $arrData = [];
        $currentTime = Carbon::now()->format('H:i'); 
        $objUser = User::where('email', $request->email)->first();
        $objcaller = User::leftJoin('permissions', 'users.permissions_id', '=', 'permissions.id')
        ->where('users.email', $request->email)
        ->select('users.*', 'permissions.name as role_name')
        ->first();


        if ($objUser) {
            $settings = \DB::table('settings')->first();
            $loginTime = $settings->caller_login ?? '10:30:00';
            $logoutTime = $settings->caller_logout ?? '17:30:00';
            
            $currentTime = \Carbon\Carbon::now()->format('H:i:s');
            
            // Telecallers: only within the calling hours; then the password and account checks apply to every role
            if ($objcaller->role_name == 'Telecaller' && ($currentTime < $loginTime || $currentTime > $logoutTime)) {
                $arrData['error'] = "You can only log in between $loginTime and $logoutTime.";
                echo json_encode($arrData);
                exit();
            }

            if (!Hash::check($request->password, $objUser->password)) {
                $arrData['error'] = trans('Wrong Password');
                echo json_encode($arrData);
                exit();
            } else if ($objUser->status == '0') {
                $arrData['error'] = trans('Account Disabled');
                echo json_encode($arrData);
                exit();
            }
            $settings = \App\Models\Setting::first();
            $licenseValidation = true;
            $response=$settings->full_response??0;
            if (empty($response) || $response=='' || $response==null) {
                $licenseValidation = false;
                echo json_encode(['error'=>'Application License Expired']);
                exit();
            }
            
            
            if($settings->daily_check != date('Y-m-d')) {
                $licenseValidation = \App\Helpers\Helper::dailyValidations();
            }
            
            try{
            if(!$licenseValidation)
            {
                if($objUser->permissionsGroup->name == 'Admin')
                {
                    echo json_encode(['error'=>'Application License Expired']);
                    exit();
                }
                else
                {
                    echo json_encode(['error'=>'There is some issue, Please contact Administrator']);
                    exit();
                }
            }
            }
            catch(Exception $e)
            {
                echo json_encode(['error'=>'Application License Expired']);
                exit();
            }
            $settings = DB::select("select FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response from leadger_settings");
            $response = json_decode($settings[0]->full_response);
            $domain=$response->data->domain;
            //dd($domain);
            $subdomain = $request->getHttpHost();
            
            // if(strtotime(date('Y-m-d')) > strtotime($response->data->expiry) || $domain != $subdomain ){
            //     echo json_encode(['error'=>'Invalid domain / Domain changed']);
            //     exit();
            // }
            $strOTP = mt_rand(1000, 9999);
            $otpmsg = 'Enter Number ('.$strOTP.') with in ';
            DB::table('users')
                ->where('email', $objUser->email)
                ->update(['login_otp' => $strOTP]);
            $arrData = [
                'success' => 'OTP sent successfully',
                'otp' => $strOTP,
                'otp_msg' => $otpmsg
            ];
        } else {
            $arrData['error'] = trans('Login Failed');
        }
        echo json_encode($arrData);
        exit();
    }

    public function validateOTP(Request $request)
    {

        $arrData = [];

        $arrInputs = $request->post();
        $otp = isset($arrInputs['otp']) ? trim($arrInputs['otp']) : '';

        if (empty($otp) || !preg_match('/^\d{4}$/', $otp)) {
            $arrData['error'] = 'Please enter a valid 4-digit OTP.';
            return json_encode($arrData);
        }

        $objUser = User::where([['email', '=', $arrInputs['email']], ['login_otp', '=', $otp]])->first();

        if ($objUser) {
            $this->validateLogin($request);

            // If the class is using the ThrottlesLogins trait, we can automatically throttle
            // the login attempts for this application. We'll key this by the username and
            // the IP address of the client making these requests into this application.
            if (method_exists($this, 'hasTooManyLoginAttempts') &&
                $this->hasTooManyLoginAttempts($request)) {
                $this->fireLockoutEvent($request);

                return $this->sendLockoutResponse($request);
            }

            if ($this->attemptLogin($request)) {
                if ($request->hasSession()) {
                    $time = time();
                    $request->session()->put('auth.password_confirmed_at', $time);
                    DB::table('users')
                        ->where('email', $objUser->email)
                        ->update(['remember_token' => $time]);
                }
                
                $this->userLogs($objUser, $request);

                $strUrl = url('/'.env('BACKEND_PATH'));

                $arrData = [
                    'url' => $strUrl,
                    'success' => 'Logged in successfully'
                ];

                if($response = $this->sendLoginResponse($request))
                    return json_encode($arrData);
            }

            // the OTP was right: the password or the account is the problem, so say that
            $this->incrementLoginAttempts($request);
            $arrData['error'] = (string)$objUser->status === '0' ? trans('Account Disabled') : trans('Wrong Password');
            return json_encode($arrData);
        }
        $this->incrementLoginAttempts($request);
        $arrData['error'] = 'Please enter a valid 4-digit OTP.';
        return json_encode($arrData);

    }
    
    protected function authenticated(Request $request, $user)
    {
        $this->userLogs($user, $request);
    }

    public function userLogs($user, Request $request)
    {
        $userLogs = UserLogs::where('user_id', $user->id)->where('logout_date', '=', null)->orderBy('id', 'desc')->first();
        if(!empty($userLogs))
        {
            $start  = new Carbon($userLogs->login_date ?? Carbon::now()->toDateTimeString() );
            if(date('Y-m-d',strtotime($userLogs->login_date)) == date('Y-m-d')) {
                $end = Carbon::now()->toDateTimeString();
            } else {
                $end = new Carbon(date('Y-m-d 22:00:00',strtotime($userLogs->login_date)));
            }
            $userLogs->logout_date = $end;
            $userLogs->logged_in_duration = $start->diff($end)->format('%H:%I:%S');
            $userLogs->scenario = 'logout';
            $userLogs->info = 'Application Total Duration';
            $userLogs->save();
        }
        
        $userLogs = new UserLogs;
        $userLogs->login_date =  Carbon::now()->toDateTimeString();
        $userLogs->ip_address  = $request->ip();
        $userLogs->user_agent_details = $request->userAgent(); 
        $userLogs->user_id = $user->id;
        $userLogs->scenario = 'login';
        $userLogs->info = "User Login"; 
        $userLogs->save();
    }

    public function logout(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            $userLogs = UserLogs::where('user_id', $user->id)
                ->whereNull('logout_date')
                ->orderBy('id', 'desc')
                ->first();
            if (!empty($userLogs)) {
                $start = new Carbon($userLogs->login_date ?? Carbon::now()->toDateTimeString());
                $end = Carbon::now()->toDateTimeString();
                $userLogs->logout_date = $end;
                $userLogs->logged_in_duration = $start->diff($end)->format('%H:%I:%S');
                $userLogs->scenario = 'logout';
                $userLogs->info = 'User Logout';
                $userLogs->save();
            }
            $this->ssoLogout($request);
        }

        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
    public function ssoLogout(Request $request)
    {
        $user=auth()->user();
        if($user->smartping_user_id){
        try {
                $username = (string) $user->smartping_user_id;
                $payload = json_encode([
                    'username' => $username
                ]);
                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://smartcloud.smartping.io/api/sso-logout",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_HTTPHEADER => [
                        "Content-Type: application/json",
                        "Content-Length: " . strlen($payload)
                    ]
                ]);

                $response = curl_exec($curl);
                $err = curl_error($curl);
                curl_close($curl);

                if ($err) {
                    \Log::error("cURL Error: " . $err);
                } else {
                    \Log::info("Logout API Response: " . $response);
                }

            } catch (\Exception $e) {
                \Log::error('Logout API call failed: ' . $e->getMessage());
            }
        }
    }

    public function license()
    {
        require_once(base_path().'/vendor/ezywing/Helper.php');
        if(\License\Helpers\Helper::LicenseValidate(env('license')))
        {
            return redirect()->to(\URL::to('/admin/login'));
        }
        return view('auth.license');
    }
    public function activateLicense(Request $request)
    {
        $arrData = [];

        $objUser = User::where('email', $request->email)->first();

        if ($objUser) {
            if (!Hash::check($request->password, $objUser->password)) {
                $arrData['error'] = trans('Wrong Password');
                echo json_encode($arrData);
                exit();
            } else if ($objUser->status == '0') {
                $arrData['error'] = trans('Account Disabled');
                echo json_encode($arrData);
                exit();
            }
            
            if($objUser->permissionsGroup->name == 'Admin')
            {
                // Validate the key that was typed (env('license') still holds the old value in this request)
                $key = trim((string)$request->license);
                require_once(base_path().'/vendor/ezywing/Helper.php');
                try{
                    $valid = $key !== '' && \License\Helpers\Helper::LicenseValidate($key, true);
                }
                catch(\Throwable $e)
                {
                    \Illuminate\Support\Facades\Log::error('[License] Activation check failed: ' . $e->getMessage());
                    echo json_encode(['error' => 'The license server could not be checked. Please try again in a moment.']);
                    exit();
                }
                if (!$valid) {
                    echo json_encode(['error' => 'Invalid License']);
                    exit();
                }

                // Valid: answer first, then save the key to .env. "php artisan serve" restarts itself when .env
                // changes - saving it before answering cut the request off ("An error occurred during license activation").
                echo json_encode(['success' => 'Activated']);
                if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); } else { @ob_flush(); flush(); }
                $path = base_path('.env');
                if (file_exists($path)) {
                    $env = file_get_contents($path);
                    $line = 'license="' . $key . '"';
                    $env = preg_match('/^license=.*$/m', $env) ? preg_replace('/^license=.*$/m', $line, $env) : rtrim($env) . PHP_EOL . $line . PHP_EOL;
                    file_put_contents($path, $env);
                }
                exit();
            }
            else
            {
                $arrData['error'] = trans('Login Failed');
            }
        }
        else
        {
            $arrData['error'] = trans('Login Failed');
        }
        echo json_encode($arrData);
        exit();
    }
}
