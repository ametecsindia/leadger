<?php

namespace App\Listeners;

use Exception;
use Auth;
use App\Models\User;
use App\Events\NewIncomingVmnLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Auth\ApplicationDefaultCredentials;


class SendVmnNotification
{
    public function SendNotification($token, $title, $body, $data = [])
    {
        $fcmurl = "https://fcm.googleapis.com/v1/projects/test-872b3/messages:send";
        $notification = [
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => $data,
            'token' => $token,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type' => 'application/json',
            ])->post($fcmurl, [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ]
            ]);

            Log::info('FCM Response:', $response->json());
            return $response->json();
        } catch (Exception $e) {
            Log::error("Error sending push notification: " . $e->getMessage());
            return false;
        }
    }

    private function getAccessToken()
    {
        $keyPath = config('services.firebase.key_path');
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyPath);

        $scopes = ['https://www.googleapis.com/auth/firebase.messaging'];
        $credentials = ApplicationDefaultCredentials::getCredentials($scopes);

        $token = $credentials->fetchAuthToken();
        Log::info("Using credentials from: " . $keyPath);
        Log::info("Access token: " . $token['access_token']);

        return $token['access_token'] ?? null;
    }

    public function handle(NewIncomingVmnLog $event)
    {
        $user = User::where('click_agent_number', $event->log->a_party_no)->first();

         // or however you're linking the call log to a user
        
		if (!$user) {
			Log::info('No user found with click_agent_number: ' .$event->log->a_party_no);
			return;
		}

        $title = "New VMN Call Received";
        $body = "CLI: {$event->log->cli}, Duration: {$event->log->duration}s";

        // Optional additional data you want to send
        $data = [
            'cli' => $event->log->cli,
            'duration' => $event->log->duration,
            'call_id' => $event->log->id,
            
        ];
        
        $data = array_map('strval', $data);
        
        // Call your sendNotification method
       

        if ($user && $user->fcm_key) {
            $this->SendNotification( $user->fcm_key,$title, $body, $data);
        }
        
    }
}  
