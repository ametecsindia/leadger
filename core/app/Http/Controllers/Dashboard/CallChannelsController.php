<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use App\Models\WebmasterSection;
use App\Helpers\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CallChannelsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the Call Channels overview page with WebRTC and Click2Call options.
     */
    public function index()
    {
        // General for all pages
        $GeneralWebmasterSections = WebmasterSection::where('status', '=', '1')->orderby('row_no', 'asc')->get();

        // Current active call channel from global settings
        $activeChannel = Helper::GeneralSiteSettings('active_call_channel') ?: 'webrtc';

        // Gather WebRTC channel statistics
        $webrtcUsers = User::where('webrtc_channel', 1)->get();
        $webrtcCount = $webrtcUsers->count();
        $webrtcLicense = 20; // default license channels

        // Gather Click2Call channel statistics
        $c2cUsers = User::where('c2c_channel', 1)->get();
        $c2cCount = $c2cUsers->count();
        $c2cLicense = 50; // default license channels

        return view('dashboard.people.call-channels', compact(
            'GeneralWebmasterSections',
            'activeChannel',
            'webrtcUsers',
            'webrtcCount',
            'webrtcLicense',
            'c2cUsers',
            'c2cCount',
            'c2cLicense'
        ));
    }

    /**
     * Toggle or switch the active call channel (mutually exclusive).
     */
    public function toggle(Request $request)
    {
        $this->validate($request, [
            'channel' => 'required|in:webrtc,click2call',
            'enabled' => 'required|in:0,1,true,false'
        ]);

        $channel = $request->input('channel');
        $enabled = filter_var($request->input('enabled'), FILTER_VALIDATE_BOOLEAN);

        $setting = Setting::find(1);
        if (!$setting) {
            $setting = new Setting();
            $setting->id = 1;
        }

        $currentActive = $setting->active_call_channel ?: 'webrtc';
        $newActive = $currentActive;

        if ($enabled) {
            // Enabling one channel disables the other (mutually exclusive)
            $newActive = $channel;
            $message = ($channel === 'webrtc') 
                ? 'WebRTC has been activated as the primary call channel.' 
                : 'Click2Call has been activated as the primary call channel.';
        } else {
            // Disabling the active channel sets it to 'none'
            if ($currentActive === $channel) {
                $newActive = 'none';
                $message = ucfirst($channel) . ' call channel has been deactivated. No calling channel is currently active.';
            } else {
                $message = 'No changes made.';
            }
        }

        $setting->active_call_channel = $newActive;
        $setting->save();

        // Clear session cache so Helper::GeneralSiteSettings reflects immediately
        \Session::forget('_Loader_Web_Settings');

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'active_channel' => $newActive,
                'message' => $message,
                'webrtc_active' => ($newActive === 'webrtc'),
                'click2call_active' => ($newActive === 'click2call')
            ]);
        }

        return redirect()->route('callChannels')->with('doneMessage', $message);
    }
}
