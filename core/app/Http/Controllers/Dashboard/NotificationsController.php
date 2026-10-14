<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Permissions;
use App\Models\Notification;
use App\Models\NotificationDetail;
use App\Models\Lead;
use App\Models\WebmasterSection;
use Illuminate\Support\Facades\DB;
use Auth;
use File;
use Illuminate\Config;
use Illuminate\Http\Request;
use Redirect;
use Helper;

use Illuminate\Support\Facades\Validator;

class NotificationsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END

        $Notifications = DB::select("select n.id,n.user_id,group_concat(lead_id) as lead_ids,n.created_at 
            from leadger_notifications n
inner join leadger_notification_detail nd
on n.id = nd.notification_id
where n.user_id = '".Auth::user()->id."'
group by n.id,n.user_id,n.created_at
order by n.created_at desc limit ".env('BACKEND_PAGINATION'));
        
        return view("dashboard.notifications.list", compact("Notifications", "GeneralWebmasterSections"));
    }

    public function view($id)
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Notification = Notification::find($id);
        if($Notification->open_status == '0')
        {
            $Notification->open_status = '1';
            $Notification->save();
        }

        $Notification = DB::select("SELECT * FROM leadger_notification_detail nd
INNER JOIN leadger_leads l
ON nd.lead_id = l.id
WHERE notification_id = $id");
        
        return view("dashboard.notifications.view", compact("Notification", "GeneralWebmasterSections"));
    }
    
    public function createAnnouncement()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();
        // General END
        
        $Teams = \App\Models\Team::get();
        
        return view("dashboard.notifications.createAnnouncement", compact("Teams", "GeneralWebmasterSections"));
    }
    
    public function getTeamMembers(Request $request)
    {
        $condition = "";
        foreach($request->team as $team)
        {
            $condition.= " find_in_set($team, team_id) or ";
        }
        $condition.= "1=2";
        $res = DB::select("select * from leadger_users where ".$condition);
        $html = "";
        foreach($res as $row)
        {
            $html .= "<option value='".$row->id."' selected>".$row->name."</option>";
        }
        echo $html;
    }
    
    public function sendAnnouncement(Request $request)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $users = DB::select("select * from leadger_users where id in (".implode(',',$request->team_members).")");
        $title = 'Leadger Announcement';
        $body = $request->announcement;
        foreach($users as $user)
        {
            if(!empty($user->fcm_key) && $user->fcm_key != '')
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
        return redirect()->action('Dashboard\NotificationsController@createAnnouncement')->with('doneMessage', 'Announcement Sent Successfully');
    }

}
