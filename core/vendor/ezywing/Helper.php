<?php
namespace License\Helpers;

use Illuminate\Support\Facades\DB;

class Helper 
{
    public static function LicenseValidate($key, $store = false)
    {
        if(empty($key))
        {
            return false;
        }
        $ip = request()->ip();

        $domain = $_SERVER['SERVER_NAME'];
        $mac = self::GetMAC();

        if($store)
        {
            $opts = array(
                "ssl" => [
                    "verify_peer"=>false,
                    "verify_peer_name"=>false,
                ],
                'http'=>array(
                  'method'=>"GET",
                  'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
                )
            );

            try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/license/'.$key,false,$context);
            $response = json_decode($response);
            }
            catch(Exception $e)
            {
                $response='';
            }
        }
        else
        {
            $settings = DB::select("select FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response from leadger_settings");
            $response = !empty($settings[0]->full_response) ? json_decode($settings[0]->full_response) : null;
            //dd($response);
        }
        if(empty($response))
        {
            return false;
        }

        if($response->status != 200)
        {
            return false;
        }
        else
        {
            if(strtotime(date('Y-m-d')) > strtotime($response->data->expiry) || $response->data->product != 3)
            {
                
                return false;
            }
            $success = 0;
            switch($response->data->license_base)
            {
                case 'ip_address':
                    if($ip == $response->data->ip_address)
                    {
                        $success = 1;
                    }
                    break;
                case 'mac':
                    if($mac == $response->data->mac)
                    {
                        $success = 1;
                    }
                    break;
                case 'domain':
                    if($domain == $response->data->domain)
                     {
                         $success = 1;
                     }
                     break;
                case 'system_id':
                    $system_id = sg_get_machine_id();
                    if($system_id == $response->data->system_id)
                    {
                        $success = 1;
                    }
                    break;
            }
            if($success == 1)
            {
                if($store)
                {
                    DB::statement("update leadger_settings set user_count = AES_ENCRYPT(TO_BASE64 ('".$response->data->user_count."'), 'MyNicotex@1'),
                            features = AES_ENCRYPT(TO_BASE64 ('".$response->data->features."'), 'MyNicotex@1'),
                            full_response = AES_ENCRYPT(TO_BASE64 ('".json_encode($response)."'), 'MyNicotex@1')");
                }
                return true;
            }
            return false;
        }
        return false;
    }
    
    private static function GetMAC(){
        if (!function_exists('system') || PHP_OS_FAMILY !== 'Windows') {
            return '';   // getmac only exists on Windows, and system() is often disabled on servers
        }
        ob_start();
        system('getmac');
        $Content = ob_get_contents();
        ob_clean();
        return substr($Content, strpos($Content,'\\')-20, 17);
    }
    
    public static function voucherValidate($key)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );

        try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/voucher/'.$key,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        return $response;
    }
    
    public static function voucherUsed($key)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );
        $ip = request()->ip();

        try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/voucher_used/'.$key.'/'.$ip,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        
        if(empty($response))
            return false;
        else if($response->status != 200)
            return false;
        return true;
    }

    public static function didVoucherValidate($key, $crmId)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );

        try{
            $context = stream_context_create($opts);
            // dd('https://lc.ametecsindia.com/api/did_voucher/'.$key,false,$context);
            $response = file_get_contents('https://lc.ametecsindia.com/api/did_voucher/'.$key.'/'.$crmId,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        return $response;
    }

    public static function didVoucherUsed($key)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );
        $ip = request()->ip();

        try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/did_voucher_used/'.$key.'/'.$ip,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        
        if(empty($response))
            return false;
        else if($response->status != 200)
            return false;
        return true;
    }

    public static function didVoucherDailyCheck($key, $crmId)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );

        try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/did_voucher_validate/'.$key.'/'.$crmId,false,$context);
            $response = json_decode($response);
            if(empty($response))
                return false;
            else if($response->status != 200)
                return false;
            if(!empty($response->deleted))
            {
                DB::statement("delete from leadger_obd_did where virtual_number in ('".implode("','",$response->deleted)."')");
            }
        }
        catch(Exception $e)
        {
            return false;
        }
        return true;
    }
    public static function flexiDialerVoucherValidate($key, $crmId)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );

        try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/flexi_dialer_voucher_validate/'.$key.'/'.$crmId,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        return $response;
    }

    public static function updateFlexiDialerVoucher($key)
    {
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: 90e86e829f54510c34c07d854a406d98"
            )
        );
        $ip = request()->ip();

        try{
            $context = stream_context_create($opts);
            $response = file_get_contents('https://lc.ametecsindia.com/api/update_flexi_dialer_voucher/'.$key.'/'.$ip,false,$context);
            $response = json_decode($response);
        }
        catch(Exception $e)
        {
            $response='';
        }
        
        if(empty($response))
            return false;
        else if($response->status != 200)
            return false;
        return true;
    }
}