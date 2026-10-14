<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable; // ✅ THIS is needed
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\CallLog;

class ProcessClick2CallLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $log;

    public function __construct(CallLog $log)
    {
        $this->log = $log;
    }

    public function handle()
    {
        $log = $this->log;

        try {
            $opts = [
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ],
                "http" => [
                    "method" => "GET",
                    "header" => "Secret-Key: ffcab6c009f568588842b54f15de6efb"
                ]
            ];

            $context = stream_context_create($opts);

            $settings = \DB::table('settings')->select(\DB::raw("FROM_BASE64(AES_DECRYPT(full_response, 'MyNicotex@1')) as full_response"))->first();
            $full_response = json_decode($settings->full_response, true);

            if (!isset($full_response['data']['crm_id'])) {
                Log::error("CRM ID not found for CallLog ID: {$log->id}");
                return;
            }

            $response = @file_get_contents("http://obd.ametecsindia.com/admin/click2call_data/{$log->lead_id}_M{$log->id}_{$full_response['data']['crm_id']}_{$log->created_by}_{$log->verified}/{$log->vmn_used}", false, $context);
            $response = json_decode($response);

            if (empty($response) || $response->status != 200) {
                Log::error("API failed for CallLog ID: {$log->id}");
                return;
            }

            $data = $response->data;

            $log->click_response = $data->response;
            $log->click_recording_url = $data->recording_url;

            $lead = Lead::find($log->lead_id);
            $uploaded_path = env('recordings_upload') . "/" . $lead->campaign_id;

            if (!is_dir($uploaded_path)) {
                mkdir($uploaded_path, 0777, true);
            }

            $filename = Carbon::parse($log->created_at)->format('Ymd_His') . '_0_' . $log->phonenumber . '.wav';
            $despath = $uploaded_path . "/" . $filename;

            $fp = fopen($despath, 'w+');
            $ch = curl_init($data->recording_url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);

            $log->recording = $filename;
            $log->file_path = $despath;
            $log->save();
        } catch (\Exception $e) {
            Log::error("Exception for CallLog ID {$log->id}: " . $e->getMessage());
        }
    }
}

