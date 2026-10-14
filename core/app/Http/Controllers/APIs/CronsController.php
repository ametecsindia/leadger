<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Click2CallLog;
use App\Jobs\ProcessClick2CallLog;
use Illuminate\Support\Facades\Http;
use App\Models\Setting;
use App\Events\NewIncomingVmnLog;
use App\Models\IncomingVmnLogs;
use App\Models\CallLog;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use App\Models\Team;
use App\Models\ImapEmail;
use App\Models\ImapSetting;
use App\Models\Notification;
use App\Models\NotificationDetail;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class CronsController extends Controller
{
    public function loginNotifications()
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        if (env('firebase_key') != '') 
        {
            $users = DB::select("SELECT * FROM ".env('DB_TABLE_PREFIX')."users WHERE shift_id IN (
SELECT id FROM ".env('DB_TABLE_PREFIX')."shifts WHERE TIME(start_time) = TIME('".date('H:i')."')
) AND id NOT IN (SELECT user_id FROM ".env('DB_TABLE_PREFIX')."tracking_logs WHERE DATE(start_at) = CURRENT_DATE)
");
            foreach($users as $user)
            {
                $title = "Login Notification";
                $body = "Dear ".$user->name.",it's time to login. Kindly do PUNCH.  If you already logged in ignore this message";
                if($user->fcm_key != '')
                {
                    $fields = ["registration_ids" => [$user->fcm_key],
                        "data" => [
                            "title" => $title,
                            "body" => $body,
                            "type" => 'login'
                        ],
                        "android" => [
                            "ttl" => "1800s",
                            "priority" => "high",
                        ],
                    ];
                    $jsonData = json_encode($fields);

                    $headers = array(
                        'Authorization: key=' . env('firebase_key'),
                        'Content-Type: application/json'
                    );

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    $result = curl_exec($ch);

                    curl_close($ch);
                }
            }
            $users = DB::select("SELECT * FROM ".env('DB_TABLE_PREFIX')."users WHERE shift_id IN (
SELECT id FROM ".env('DB_TABLE_PREFIX')."shifts WHERE TIME('".date('H:i')."') > TIME(TIME(start_time)+TIME('00:15')) AND TIME('".date('H:i')."') < TIME(TIME(start_time)+TIME('01:00'))
) AND id NOT IN (SELECT user_id FROM ".env('DB_TABLE_PREFIX')."tracking_logs WHERE DATE(start_at) = CURRENT_DATE)
");
            foreach($users as $user)
            {
                $title = "Login Notification";
                $body = "Dear ".$user->name.",avoid LATE MARK,  it's time to login. Kindly do PUNCH.  If you already logged in ignore this message";
                if($user->fcm_key != '')
                {
                    $fields = ["registration_ids" => [$user->fcm_key],
                        "data" => [
                            "title" => $title,
                            "body" => $body,
                            "type" => 'login'
                        ],
                        "android" => [
                            "ttl" => "1800s",
                            "priority" => "high",
                        ],
                    ];
                    $jsonData = json_encode($fields);

                    $headers = array(
                        'Authorization: key=' . env('firebase_key'),
                        'Content-Type: application/json'
                    );

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    $result = curl_exec($ch);

                    curl_close($ch);
                }
            }
        }
    }
    
    public function absentNotifications()
    {
        if(env('firebase_key') != '')
        {
            $teams = Team::all();
            foreach($teams as $team)
            {
                $managers = DB::select("SELECT ".env('DB_TABLE_PREFIX')."users.* FROM ".env('DB_TABLE_PREFIX')."users 
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions
ON ".env('DB_TABLE_PREFIX')."users.permissions_id = ".env('DB_TABLE_PREFIX')."permissions.id
WHERE ".env('DB_TABLE_PREFIX')."permissions.name = 'Manager'
AND FIND_IN_SET(".$team->id.",team_id)");
                $reporting_managers = [];
                foreach($managers as $manager)
                {
                    $reporting_managers[] = $manager->email;
                }

                $absent_list = DB::select("SELECT ".env('DB_TABLE_PREFIX')."users.* FROM ".env('DB_TABLE_PREFIX')."users 
INNER JOIN ".env('DB_TABLE_PREFIX')."permissions
ON ".env('DB_TABLE_PREFIX')."users.permissions_id = ".env('DB_TABLE_PREFIX')."permissions.id
WHERE team_id = ".$team->id." AND ".env('DB_TABLE_PREFIX')."permissions.name IN ('Telecaller', 'Team Leader', 'Sales Person')
AND ".env('DB_TABLE_PREFIX')."users.id NOT IN (SELECT user_id FROM ".env('DB_TABLE_PREFIX')."tracking_logs WHERE DATE(created_at) = CURRENT_DATE)");
                if(!empty($absent_list))
                {
                    $strSubject = "Absent List";
                    $strTemplate = "<table border='1' cellspacing='0' cellpadding='0'><tr><th>Name</th></tr>";
                    foreach($absent_list as $absent)
                    {
                        $strTemplate .= "<tr><td>".$absent->name."</td></tr>";
                    }
                    $strTemplate .= "</table>";
                    Helper::sendMail(implode(',', $reporting_managers), $strSubject, $strTemplate);
//                    $objEmailSetting = \App\Models\EmailSetting::first();
//                    if ($objEmailSetting->verified) {
//                        \Illuminate\Support\Facades\Notification::route('mail', $reporting_manager->email)->notifyNow(new \App\Notifications\SendCampaignEmail($strSubject, $strTemplate));
//                    }
                }
            }
        }
    }
    
    public function syncEmailsFromImap()
    {
        $imapSettings = ImapSetting::where("status",1)->get();
        foreach($imapSettings as $ImapSetting)
        {
            $whitelistArr = !empty($ImapSetting->whitelist_emails) ? explode(',',$ImapSetting->whitelist_emails) : [];
            $inbox=imap_open("{".$ImapSetting->host.":".$ImapSetting->port."/".$ImapSetting->encryption."}".$ImapSetting->folder."", $ImapSetting->username, $ImapSetting->password);
            $count = imap_num_msg($inbox);

            // Loop through the emails and retrieve the message headers and body
            for ($i = $count; $i > 0; $i--) {
                $header = imap_headerinfo($inbox, $i);
				if(strtotime($header->date) < strtotime('-7 days'))
				{
					break;
				}
                if(!in_array(strpos($header->fromaddress,'<') ? explode('>',explode('<',$header->fromaddress)[1])[0] : $header->fromaddress, $whitelistArr))
                {
                    continue;
                }
                $exists = DB::select("select id from ".env('DB_TABLE_PREFIX')."imap_emails 
                        where imap_setting_id = ".$ImapSetting->id."
                        and from_email = '".$header->fromaddress."'
                        and subject = '".(isset($header->subject) ? $header->subject : '')."'
                        and email_date = '".date('Y-m-d H:i:s', strtotime($header->date))."'");
                if(!empty($exists))
                {
                    break;
                }
                $body = imap_fetchbody($inbox, $i, 2);
                
                $ImapEmail = new ImapEmail;
                $ImapEmail->imap_setting_id = $ImapSetting->id;
                $ImapEmail->from_email = $header->fromaddress;
                $ImapEmail->subject = (isset($header->subject) ? $header->subject : '');
                $ImapEmail->email_date = date('Y-m-d H:i:s', strtotime($header->date));
                $ImapEmail->body = trim( utf8_encode( quoted_printable_decode($body)));
                $ImapEmail->mail_parsed = 0;
                $ImapEmail->save();
            }
            imap_close($inbox);
        }
    }
    
    public function reminderNotifications()
    {
        $res = DB::select("SELECT * FROM leadger_leads 
WHERE DATE(follow_up_on) = '".date('Y-m-d')."'
AND follow_up_on-INTERVAL reminder MINUTE BETWEEN CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP + INTERVAL 30 MINUTE
ORDER BY assigned_to");
        for($i=0;$i<count($res); $i++)
        {
            if($i==0 || $res[$i-1]->assigned_to != $res[$i]->assigned_to)
            {
                $Notification = new Notification;
                $Notification->user_id = $res[$i]->assigned_to;
                $Notification->save();
            }
            $NotificationDetail = new NotificationDetail;
            $NotificationDetail->notification_id = $Notification->id;
            $NotificationDetail->lead_id = $res[$i]->id;
            $NotificationDetail->message = 'Reminder';
            $NotificationDetail->follow_up_on = $res[$i]->follow_up_on;
            $NotificationDetail->save();
        }
        $res = DB::select("SELECT * FROM leadger_leads 
WHERE DATE(follow_up_on) = '".date('Y-m-d')."'
AND follow_up_on-INTERVAL reminder MINUTE BETWEEN CURRENT_TIMESTAMP AND CURRENT_TIMESTAMP + INTERVAL 30 MINUTE
and sales_person_id is not null
ORDER BY sales_person_id");
        for($i=0;$i<count($res); $i++)
        {
            if($i==0 || $res[$i-1]->sales_person_id != $res[$i]->sales_person_id)
            {
                $Notification = new Notification;
                $Notification->user_id = $res[$i]->sales_person_id;
                $Notification->save();
            }
            $NotificationDetail = new NotificationDetail;
            $NotificationDetail->notification_id = $Notification->id;
            $NotificationDetail->lead_id = $res[$i]->id;
            $NotificationDetail->message = 'Reminder';
            $NotificationDetail->follow_up_on = $res[$i]->follow_up_on;
            $NotificationDetail->save();
        }
    }
    public function fetchClick2CallInBatches()
{
    $batchSize = 100;

    CallLog::where('call_type', 'click2call')
        ->where(function($q) {
            $q->whereNull('click_response')->orWhereNull('click_recording_url');
        })
        ->orderBy('id')
        ->chunk($batchSize, function ($logs) {
            foreach ($logs as $log) {
                ProcessClick2CallLog::dispatch($log);
            }
        });

    return response()->json(['message' => 'Recording fetch jobs dispatched successfully.']);
}
    
    public function fetchClick2call()
    {
        $logs = \App\Models\CallLog::where('call_type', 'click2call')
                ->whereDate(DB::raw("DATE(created_at)"), '>=', date('Y-m-d',strtotime('-5 days')))
                ->whereNull('click_response')->get();
        
        $opts = array(
            "ssl" => [
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ],
            'http'=>array(
              'method'=>"GET",
              'header'=>"Secret-Key: ffcab6c009f568588842b54f15de6efb"
            )
        );
        $settings = DB::table('settings')->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
        $full_response = json_decode($settings->full_response, true);
        if(!isset($full_response['data']['crm_id']))
        {
            echo 'CRM ID is not available';die;
        }
        foreach($logs as $log)
        {
            try{
                $context = stream_context_create($opts);
                $response = file_get_contents('http://obd.ametecsindia.com/admin/click2call_data/'.$log->lead_id.'_M'.$log->id.'_'.$full_response['data']['crm_id'].'_'.$log->created_by.'_'.$log->verified.'/'.$log->vmn_used,false,$context);
                $response = json_decode($response);
            }
            catch(Exception $e)
            {
                $response='';
            }

            if(empty($response))
            {
                continue;
            }
            if($response->status != 200)
            {
                continue;
            }
            $data = $response->data;
            
            $log->click_response = $data->response;
            $log->click_recording_url = $data->recording_url;
            $log->save();

            $lead = \App\Models\Lead::find($log->lead_id);
            
            $uploaded_path = env('recordings_upload');
            $crm_id = $full_response['data']['crm_id'];
            $campaign_id = $lead->campaign_id;
            
            // Construct the folder path: crm_id > campaign_id > recording
            $folderPath = $uploaded_path . DIRECTORY_SEPARATOR . $crm_id . DIRECTORY_SEPARATOR . $campaign_id;
            
            // Create the directory structure if it doesn’t exist
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true); // Ensure recursive creation of directories
            }
            
            // Generate the filename
            $filename = Carbon::parse($log->created_at)->format('Ymd_His') .'_0_'.$log->phonenumber.'.wav';
            
            // Set the full path to the file
            $despath = $folderPath . DIRECTORY_SEPARATOR . $filename;

            $fp = fopen ($despath, 'w+');//This is the file where we save the information
            $ch = curl_init($data->recording_url);//Here is the file we are downloading
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $log->recording = $filename;
            $log->save();
            
        }
        if(\App\Helpers\TcfgHelper::incoming_vmn)
        {
            $incoming_click2call_vmn = DB::select("select * from leadger_obd_did where click2call = 1");
            $fields = array(
                "vmn" => array_column($incoming_click2call_vmn,'virtual_number')
            );
            $json = json_encode($fields);
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,"http://obd.ametecsindia.com/admin/incoming_data/");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
                'Secret-Key: ffcab6c009f568588842b54f15de6efb'
            ));
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            $server_output = curl_exec($ch);
            $errors = curl_error($ch);
            curl_close($ch);
            if(empty($errors))
            {
                $response = json_decode($server_output);
                if($response->status != 200)
                {
                    echo "not 200";
                    die;
                }
                $data = $response->data;
                
                foreach($data as $log)
                {
                    
                    $incoming = \App\Models\IncomingVmnLogs::where('call_id', $log->call_id)->first();
                    if(!empty($incoming))
                    {
                        continue;
                    }
                    $incoming = new \App\Models\IncomingVmnLogs();
                    
                    $uploaded_path = env('recordings_upload')."\\incoming";
                    if(is_dir($uploaded_path) === false)
                    {
                            mkdir($uploaded_path);
                    }

                    $despath = $duration = '';
                    if($log->recording_url != 'No Voice')
                    {
                        $filename = Carbon::parse($log->created_at)->format('Ymd_His') .'_0_'.$log->cli.'.wav';
                        $despath = $uploaded_path. "\\" . $filename;

                        $fp = fopen ($despath, 'w+');//This is the file where we save the information
                        $ch = curl_init($log->recording_url);//Here is the file we are downloading
                        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
                        curl_setopt($ch, CURLOPT_FILE, $fp);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_exec($ch);
                        curl_close($ch);
                        fclose($fp);
                    }
                    
                    $incoming->call_id = $log->call_id;
                    $incoming->dni = $log->dni;
                    $incoming->a_party_no = $log->a_party_no;
                    $incoming->a_start_time = $log->a_start_time;
                    $incoming->a_dial_status = $log->a_dial_status;
                    $incoming->cli = $log->cli;
                    $incoming->recording_url = $log->recording_url;
                    $incoming->duration = $log->duration;
                    $incoming->response = $log->response;
                    $incoming->file_path = $despath;
                    $incoming->save();
                    
                }
            }

        }
        
        $dialerlogs = \App\Models\DialerLog::whereDate(DB::raw("DATE(created_at)"), '>=', date('Y-m-d',strtotime('-5 days')))
                ->whereNull('click_response')
                ->whereNotNull('uuid')->get();

        foreach($dialerlogs as $log)
        {
            try{
                $context = stream_context_create($opts);
                $response = file_get_contents('http://obd.ametecsindia.com/admin/click2call_data/'.$log->uuid.'/'.$log->vmn_used,false,$context);
                $response = json_decode($response);
            }
            catch(Exception $e)
            {
                $response='';
            }
            if(empty($response))
            {
                continue;
            }
            if($response->status != 200)
            {
                continue;
            }
            $data = $response->data;
            
            $log->click_response = $data->response;
            $log->click_recording_url = $data->recording_url;
            $log->save();

            $uploaded_path = env('recordings_upload');
// Get crm_id and campaign_id
            $crm_id = $full_response['data']['crm_id'];
            $campaign_id = $log->campaign_id;

            // Construct the folder path: crm_id > dialer > campaign_id > recording
            $folderPath = $uploaded_path . DIRECTORY_SEPARATOR . $crm_id . DIRECTORY_SEPARATOR . 'dialer' . DIRECTORY_SEPARATOR . $campaign_id;

            // Create the directory structure if it doesn’t exist
            if (!is_dir($folderPath)) {
                mkdir($folderPath, 0755, true); // Ensure recursive creation of directories
            }

            // Generate the filename
            $filename = Carbon::parse($log->created_at)->format('Ymd_His') . '_0_' . $log->phone_number . '.wav';
            if (!empty($data->a_start_time)) {
                $filename = Carbon::parse($log->created_at)->format('Ymd_His') . '_' . date('His', strtotime($data->a_start_time)) . '_0_' . $log->phone_number . '.wav';
            }

            // Set the full path to the file
            $despath = $folderPath . DIRECTORY_SEPARATOR . $filename;

            $fp = fopen ($despath, 'w+');//This is the file where we save the information
            $ch = curl_init($data->recording_url);//Here is the file we are downloading
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
            
//            $duration = Common::getDuration($despath);

            $log->file_path = $despath;
//            $log->duration = $duration;
            $log->save();
        }
        
    }
    
    private function authAPI()
    {
        $fields = array(
            "UserName" => \App\Helpers\TcfgHelper::obd_username,
            "Password" => \App\Helpers\TcfgHelper::obd_password
        );
        $json = json_encode($fields);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,"https://cts.myvi.in:8443/dynamicobd2/api/AuthJwt/Authenticate/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($json)
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $server_output = curl_exec($ch);
        $errors = curl_error($ch);
        curl_close($ch);
        if(empty($errors))
            return $server_output;
        return '';
    }

    private function createCampaignAPI($token, $campaign)
    {
	$dom = new \DOMDocument();

        $dom->formatOutput = true;

        $root = $dom->createElement('dataset');

        $client_id = $dom->createElement('client_id', '100');
        $root->appendChild($client_id);

        $campname = $dom->createElement('campname', $campaign->name.'_'.strtotime(date('Y-m-d H:i:s')));
        $root->appendChild($campname);

        $fromdt = $dom->createElement('fromdt', date('Y-m-d', strtotime($campaign->schedule_start)));
        $root->appendChild($fromdt);

        $todt = $dom->createElement('todt', date('Y-m-d', strtotime($campaign->schedule_end)));
        $root->appendChild($todt);

        $fromtime = $dom->createElement('fromtime', date('H:i', strtotime($campaign->schedule_start)));
        $root->appendChild($fromtime);

        $totime = $dom->createElement('totime', date('H:i', strtotime($campaign->schedule_end)));
        $root->appendChild($totime);

        $cli = $dom->createElement('cli', $campaign->did);
        $root->appendChild($cli);

        $Retry_interval = $dom->createElement('Retry_interval', '10');
        $root->appendChild($Retry_interval);

        $flow = $dom->createElement('flow');

        $voice = $dom->createElement('voice', '100');
        $flow->appendChild($voice);

        $dtmfreq = $dom->createElement('dtmfreq', 'N');
        $flow->appendChild($dtmfreq);

        $dtmfvaltype = $dom->createElement('dtmfvaltype', '1');
        $flow->appendChild($dtmfvaltype);

        $dtmfvalparam = $dom->createElement('dtmfvalparam', '1');
        $flow->appendChild($dtmfvalparam);

        $dtmfplayback = $dom->createElement('dtmfplayback', '1');
        $flow->appendChild($dtmfplayback);

        $wronginput = $dom->createElement('wronginput', '10');
        $flow->appendChild($wronginput);

        $invalidinput = $dom->createElement('invalidinput', '11');
        $flow->appendChild($invalidinput);

        $root->appendChild($flow);

        $dom->appendChild($root);

        $xml_string = $dom->saveXML();
        $xml = str_replace('<?xml version="1.0"?>','',$xml_string);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,"https://cts.myvi.in:8443/dynamicobd2/api/OBDCampaign/CreateCampaign");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/xml',
            'Authorization: Bearer '.$token,
            'Content-Length: ' . strlen($xml)
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $server_output = curl_exec($ch);
        $errors = curl_error($ch);
        curl_close($ch);
        if(empty($errors))
        {
            $xml = simplexml_load_string($server_output);
            if($xml->Status != 'Failed')
            {
                return $xml->CampaignId;
            }
            else
            {
                echo $xml->Message;
            }
        }
        return '';
    }

    private function recordingUpload($token, $campaign)
    {
        
        $path = 'uploads/obd/';
        
        $mp3 = $path.$campaign->audio_file;
        $wav = $path."F".$campaign->id.".wav";

        shell_exec("ffmpeg -y -i ".$mp3." -ar 8000 -ac 1 -acodec pcm_u8 ".$wav."");

        $cfile = curl_file_create($wav,'audio/wav',"F".$campaign->id.".wav");
        $fields = array(
            "campaignid" => $campaign->vi_campaign_id,
            "fileid" => "AI00".$campaign->id,
            "file" => $cfile
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,"https://cts.myvi.in:8443/dynamicobd2/api/OBDCampaign/VoiceUpload");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer '.$token
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $server_output = curl_exec($ch);
        $errors = curl_error($ch);
        curl_close($ch);
        if(empty($errors))
        {
            $xml = simplexml_load_string($server_output);
            if($xml->Message == 'Voice File Saved')
            {
                return 'Success';
            }        
        }
        return '';
    }

    private function mobileAPI($token, $campaign, $details)
    {
	$dom = new \DOMDocument();

        $dom->formatOutput = true;

        $root = $dom->createElement('dataset');

        $campaignid = $dom->createElement('campaignid', $campaign->vi_campaign_id);
        $root->appendChild($campaignid);
        
        foreach($details as $detail)
        {
            $record = $dom->createElement('record');

            $msisdn = $dom->createElement('msisdn', $detail->mobile_number);
            $record->appendChild($msisdn);

            $ttstype = $dom->createElement('ttstype', 'F');
            $record->appendChild($ttstype);

            $voice = $dom->createElement('voice');

            $scriptid = $dom->createElement('scriptid', '100');
            $voice->appendChild($scriptid);

            $script = $dom->createElement('script', '$$AI00'.$campaign->id.'$$');
            $voice->appendChild($script);

            $record->appendChild($voice);

            $root->appendChild($record);
        }

        $dom->appendChild($root);

        $xml_string = $dom->saveXML();
        $xml = str_replace('<?xml version="1.0"?>','',$xml_string);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,"https://cts.myvi.in:8443/dynamicobd2/api/OBDCampaign/UploadBase");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/xml',
            'Authorization: Bearer '.$token,
            'Content-Length: ' . strlen($xml)
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $server_output = curl_exec($ch);
        $errors = curl_error($ch);
        curl_close($ch);
        if(empty($errors))
        {
            $xml = simplexml_load_string($server_output);
            if($xml->Status != 'Failed')
            {
                return 'Success';
            }
            else
            {
                error_log($server_output);
            }
        }
        else
        {
            error_log($server_output);
            error_log(print_r($errors,1));
        }
        return '';
        
    }

    public function executeObdCampaigns()
    {
        $campaign = \App\Models\ObdCampaign::wherein('status',[0,1])->orderBy('status', 'desc')->first();
        if(empty($campaign))
        {
            echo "No Campaigns Found";
            die;
        }
        $token = str_replace('"','',$this->authAPI());
        if(empty($token))
        {
            echo "Auth API issue";
            die;
        }
        if(empty($campaign->vi_campaign_id))
        {
            //campaign creation
            $viCampaignId = $this->createCampaignAPI($token, $campaign);
            if(empty($viCampaignId))
            {
                echo "Create Campaign API issue";
                die;
            }
        
            $campaign->vi_campaign_id = $viCampaignId;
            $campaign->status = 1;

            $uploaded = $this->recordingUpload($token, $campaign);
            if(empty($uploaded))
            {
                echo "File Upload API error";
                die;
            }
            $campaign->save();
        }
        $limit = \App\Helpers\TcfgHelper::TPH;
        $campaign_leads = \App\Models\ObdCampaignDetail::where('obd_campaign_id',$campaign->id)
                ->where('status',0)->orderBy('id')->take($limit)->get();
        if($campaign_leads->count() == 0)
        {
            $campaign->status = 2;
            $campaign->save();

            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Refresh:5 url=".route('api.obd.execute'));
            exit();
        }
        $success = $this->mobileAPI($token, $campaign, $campaign_leads);
        if(empty($success))
        {
            echo "Mobile API issue";
            die;
        }
        foreach($campaign_leads as $lead)
        {
            $lead->status = 1;
            $lead->save();
        }

        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Refresh:5 url=".route('api.obd.execute'));
        exit();
    }



public function fetchIncomingVmnLogs()
{
    if (!\App\Helpers\TcfgHelper::incoming_vmn) {
        return response()->json(['status' => 403, 'message' => 'Feature is disabled']);
    }

    $incoming_click2call_vmn = DB::select("SELECT * FROM leadger_obd_did WHERE click2call = 1");

    $settings = DB::table('settings')
        ->select(DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))
        ->first();

    $full_response = json_decode($settings->full_response, true);
    $crm_id = $full_response['data']['crm_id'];
    
    $app_crm = [$crm_id];

    $fields = [
        'vmn' => array_column($incoming_click2call_vmn, 'virtual_number'),
        'crm' => $app_crm
    ];

    $json = json_encode($fields);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://obd.ametecsindia.com/admin/incoming_data/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json),
        'Secret-Key: ffcab6c009f568588842b54f15de6efb'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $server_output = curl_exec($ch);

    if ($server_output === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return response()->json(['status' => 500, 'message' => 'Curl execution failed', 'error' => $error]);
    }

    curl_close($ch);
    $response = json_decode($server_output);
        if (!$response ) {
        return response()->json([
            'status' => 400,
            'message' => 'API returned empty response',
            'response' => $server_output
        ]);
    }
    if ($response->status != 200 || !isset($response->data)) {
        return response()->json([
            'status' => 400,
            'message' => 'API returned error',
            'response' => $server_output
        ]);
    }
    $todayCallIds = IncomingVmnLogs::whereDate('created_at', Carbon::today())
                ->orWhereDate('updated_at', Carbon::today())
                ->pluck('call_id')  // get just call_id column as collection
                ->toArray();    
    $saved = 0;
    foreach ($response->data as $log) {
            if (in_array($log->call_id, $todayCallIds)) {
                continue;
            }

        $uploaded_path = rtrim(env('recordings_upload'), '/\\') . "/incoming";
        if (!is_dir($uploaded_path)) {
            mkdir($uploaded_path, 0755, true);
        }

        $despath = '';
        if ($log->recording_url !== 'No Voice') {
            $filename = Carbon::parse($log->created_at)->format('Ymd_His') . '_0_' . $log->cli . '.wav';
            $despath = $uploaded_path . "/" . $filename;

            $fp = fopen($despath, 'w+');
            $ch = curl_init($log->recording_url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        $incoming = new IncomingVmnLogs();
        $incoming->call_id = $log->call_id;
        $incoming->dni = $log->dni;
        $incoming->a_party_no = $log->a_party_no;
        $incoming->a_start_time = $log->a_start_time;
        $incoming->cli = $log->cli;
        $incoming->recording_url = $log->recording_url;
        $incoming->duration = $log->duration;
        $incoming->response = $log->response;
        $incoming->file_path = $despath;
        $incoming->save();
        event(new \App\Events\NewIncomingVmnLog($incoming));
        $saved++;
    }

    return response()->json([
        'status' => 200,
        'message' => "$saved new VMN log(s) saved successfully.",
        'total_received' => count($response->data),
        'saved' => $saved
    ]);
}
public function updateAllClick2CallResponses()
{
   $logs = CallLog::whereNull('click_response')
    ->whereBetween('created_at', ['2025-07-07 00:00:00', '2025-07-12 23:59:59'])
    ->get();


    // Path to your CA cert file (adjust this path as needed)
    $caCertPath = base_path('public\ssl\cacert.pem');


    foreach ($logs as $log) {
        $reference_id = $log->reference_id;
        $vmn = $log->vmn_used;

        $url = "https://obd.ametecsindia.com/admin/click2call_data/{$reference_id}/{$vmn}";
        
        // Initialize cURL
        $ch = curl_init();

        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Secret-Key: ffcab6c009f568588842b54f15de6efb',
            ],
            CURLOPT_CAINFO => $caCertPath,  // Use your CA cert file here
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
        ]);

        // Execute the request
        $result = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($err) {
            \Log::warning("cURL error for ref_id {$reference_id}: {$err}");
            continue;
        }

        if ($httpCode !== 200) {
            \Log::warning("HTTP status {$httpCode} for ref_id {$reference_id}");
            continue;
        }

        $json = json_decode($result, true);

        if (!isset($json['data']['response'])) {
            \Log::warning("Missing response field for ref_id {$reference_id}");
            continue;
        }

        $rawResponse = $json['data']['response'];
        $parsedResponse = json_decode($rawResponse, true);

        $leadIdFromResponse = $parsedResponse['REF_ID']  ?? null;
        if (!$leadIdFromResponse) {
            \Log::warning("Missing lead_id in response for ref_id {$reference_id}");
            continue;
        }

        // Duration calculation

        $bEnd = $parsedResponse['RecordVoice'] ?? null;

        

        // Update the log
        CallLog::where('reference_id', $leadIdFromResponse)->update([
            'click_response' => $rawResponse,
            'click_recording_url' => $bEnd,
        ]);
    }

    return response()->json(['message' => 'All Click2Call logs updated']);
}

}
