<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\Controller;
use App\Services\CallRecordingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelephonyWebhookController extends Controller
{
    protected $recordingService;

    public function __construct(CallRecordingService $recordingService)
    {
        $this->recordingService = $recordingService;
    }

    /**
     * Handle incoming telephony callback / webhook from provider.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request)
    {
        $rawInput = file_get_contents('php://input');
        $payload = $request->all();

        if (empty($payload) && !empty($rawInput)) {
            $decoded = json_decode($rawInput, true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $result = $this->recordingService->processWebhook($payload, $rawInput, $request);

        return response()->json($result, $result['code'] ?? 200);
    }
}
