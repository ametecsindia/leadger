<?php

namespace App\Http\Controllers\Click2Call;

use App\Http\Controllers\Controller;
use App\Models\C2c\Call;
use App\Services\Click2Call\CallingRules;
use App\Services\Click2Call\CallResult;
use App\Services\Click2Call\Click2CallGateway;
use App\Services\Click2Call\VmnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * The calling page and its endpoints. Start-call checks in the same order as LeadGER's DialerController::click2call:
 * validation -> Click2Call enabled + agent number -> calling hours / daily limit -> per-agent lock -> VMN check ->
 * call row ("initiated") -> provider -> answer. Added: every call-id endpoint checks the call belongs to the user.
 */
class CallController extends Controller
{
    public function dialer(Request $request)
    {
        $user = $request->user();
        return view('click2call.dialer', [
            'vmns' => VmnService::listFor($user, 'click2call'),
            'defaultVmn' => $user->c2c_default_number,
            'canCall' => $user->canClick2Call(),
            'number' => preg_match('/^[0-9+ ]{1,15}$/', (string) $request->query('number')) ? $request->query('number') : '',
        ]);
    }

    /** Numbers of an optional pool (smart31 | 1600) for the VMN selector. */
    public function pool(Request $request, string $pool)
    {
        abort_unless(isset(VmnService::POOLS[$pool]), 404);
        return response()->json(['vmns' => VmnService::poolFor($request->user(), 'click2call', $pool)]);
    }

    public function start(Request $request, Click2CallGateway $gateway, CallingRules $rules)
    {
        $data = $request->validate([
            'mobile' => ['required', 'regex:/^[0-9]+$/', 'min:10', 'max:20'],
            'vmn' => ['required', 'string', 'max:20'],
            'pool' => ['nullable', 'in:smart31,1600'],
            'source' => ['nullable', 'in:manual,campaign'],
            'lead_id' => ['nullable', 'integer'],
            'campaign_id' => ['nullable', 'integer'],
        ], [
            'mobile.required' => 'Please enter mobile number.',
            'mobile.regex' => 'Phone number must contain only numeric digits (0-9).',
            'mobile.min' => 'Phone number must be at least 10 digits.',
            'vmn.required' => 'Please select VMN.',
        ]);
        $user = $request->user();

        if (!$user->canClick2Call()) {
            return response()->json(['success' => false, 'message' => 'Click2Call is not enabled for your account, or your agent number is missing. Ask an admin.'], 403);
        }

        // Calling hours + daily limit (checked first without counting; the attempt is counted just before dialling)
        $allowed = $rules->check($data['mobile'], $user, false);
        if (!$allowed['allowed']) {
            return response()->json(['success' => false, 'message' => $allowed['message'], 'reason' => $allowed['reason']], $allowed['code']);
        }

        $vmnCheck = VmnService::check($user, $data['vmn'], 'click2call', $data['pool'] ?? null);
        if (!$vmnCheck['ok']) {
            return response()->json(['success' => false, 'message' => $vmnCheck['message']], 422);
        }

        $response = $rules->withAgentLock($user, function () use ($data, $user, $gateway, $rules) {
            $customer = VmnService::normalize($data['mobile']);
            $counted = $rules->check($customer, $user, true);     // counts this attempt (atomic)
            if (!$counted['allowed']) {
                return response()->json(['success' => false, 'message' => $counted['message'], 'reason' => $counted['reason']], $counted['code']);
            }

            $call = Call::create([
                'user_id' => $user->getKey(),
                'source' => $data['source'] ?? 'manual',
                'lead_id' => $data['lead_id'] ?? null,
                'campaign_id' => $data['campaign_id'] ?? null,
                'customer_number' => $customer,
                'agent_number' => VmnService::normalize($user->click2CallAgentNumber()),
                'vmn' => VmnService::normalize($data['vmn']),
                'ref_id' => (string) Str::uuid(),
                'call_state' => 'initiated',
                'started_at' => now(),
            ]);

            $res = $gateway->start($call->vmn, $call->agent_number, $call->customer_number, $call->ref_id);
            if (!$res['ok']) {
                $rules->release($customer);                       // the attempt is given back, as in LeadGER
                $call->update(['call_state' => 'failed', 'ended_at' => now(), 'error' => $res['message']]);
                return response()->json(['success' => false, 'message' => $res['message']], 400);
            }
            $call->update(['provider_call_id' => $res['provider_call_id']]);

            // "initiated": the provider queued the call. Whether anybody answered comes from the call record.
            return response()->json([
                'success' => true, 'message' => 'Call initiated successfully',
                'id' => $call->id, 'reference_id' => $call->ref_id, 'provider_call_id' => $call->provider_call_id,
            ]);
        });

        return $response ?? response()->json(['success' => false, 'message' => 'Another call is currently being processed for your account. Please wait a moment.'], 429);
    }

    /**
     * Live status for the page (polled every 2 s). While the call is live, the gateway is asked for its record
     * (LeadGER: DialerController::callStatus + fetchGatewayCallRecord, 3 s timeout).
     */
    public function status(Request $request, Call $call, Click2CallGateway $gateway, CallResult $result)
    {
        $this->authorizeCall($request, $call);
        if (!$call->isFinal() && ($data = $gateway->callData($call->ref_id, $call->vmn, 3))) {
            $call = $result->apply($call, $data['cdr'], $data['recording_url']);
        }

        $messages = [
            'initiated' => 'Calling...',
            'ringing' => 'Ringing customer...',
            'connected' => 'Call Successful / Connected',
            'completed' => 'Call Ended',
            'busy' => 'Customer line is busy',
            'no-answer' => 'Customer did not answer',
            'failed' => $call->error ?: 'Call failed',
        ];
        return response()->json([
            'status' => $call->call_state,
            'message' => $messages[$call->call_state] ?? 'Calling...',
            'final' => $call->isFinal(),
            'talk_seconds' => $call->talk_seconds,
            'connected_for' => $call->call_state === 'connected' && $call->answered_at ? (int) now()->diffInSeconds($call->answered_at, true) : null,
            'recording' => $call->recording_status,
        ]);
    }

    /** The agent's outcome after the call (LeadGER: savelog / save-disposition). */
    public function outcome(Request $request, Call $call)
    {
        $this->authorizeCall($request, $call);
        $data = $request->validate([
            'outcome' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'follow_up_at' => ['nullable', 'date', 'after:now'],
        ]);
        $call->update($data + ($call->isFinal() ? [] : ['call_state' => 'completed', 'ended_at' => $call->ended_at ?? now()]));
        return response()->json(['success' => true, 'message' => 'Call outcome saved.']);
    }

    /** Play (HTTP Range, ?play=1) or download a recording; only the agent of the call or an admin. */
    public function recording(Request $request, Call $call)
    {
        $this->authorizeCall($request, $call);
        $disk = Storage::disk(config('click2call.recording_disk'));
        abort_unless($call->recording_path && $disk->exists($call->recording_path), 404, 'The recording is not available.');
        $path = $disk->path($call->recording_path);
        return $request->boolean('play')
            ? response()->file($path, ['Accept-Ranges' => 'bytes'])
            : response()->download($path, basename($call->recording_path));
    }

    private function authorizeCall(Request $request, Call $call): void
    {
        $user = $request->user();
        abort_unless($user->isC2cAdmin() || (int) $call->user_id === (int) $user->getKey(), 403, 'This call belongs to another user.');
    }
}
