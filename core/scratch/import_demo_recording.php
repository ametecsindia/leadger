<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\CallLog;
use App\Models\CallAiAnalysis;
use Illuminate\Support\Carbon;

echo "--- 1. Ensuring Agent Akshitha ---\n";
$agent = User::where('name', 'like', '%Akshitha%')->first();
if (!$agent) {
    die("Error: Akshitha not found in users table.\n");
}
echo "Agent: {$agent->name} (ID: {$agent->id})\n";

echo "--- 2. Creating or Finding Campaign 'September Home Loan' ---\n";
$campaign = Campaign::where('name', 'September Home Loan')->first();
if (!$campaign) {
    $campaign = new Campaign();
    $campaign->name = 'September Home Loan';
    $campaign->team_id = $agent->team_id ?? 1;
    $campaign->status = 1;
    $campaign->public = 0;
    $campaign->number_masking = 0;
    $campaign->created_by = $agent->id;
    $campaign->save();
    echo "Created new Campaign: September Home Loan (ID: {$campaign->id})\n";
} else {
    echo "Existing Campaign: September Home Loan (ID: {$campaign->id})\n";
}

echo "--- 3. Creating or Finding Customer Lead 'Rahul' ---\n";
$lead = Lead::where('campaign_id', $campaign->id)
    ->where('name', 'Rahul')
    ->first();

if (!$lead) {
    $lead = new Lead();
    $lead->campaign_id = $campaign->id;
    $lead->name = 'Rahul';
    $lead->phonenumber = '9876543210';
    $lead->assigned_to = $agent->id;
    $lead->status = 14;
    $lead->source = 'Campaign';
    $lead->remarks = 'Inquired about home loan options. Follow-up tomorrow at 3 PM.';
    $lead->created_by = $agent->id;
    $lead->save();
    echo "Created new Lead: Rahul (ID: {$lead->id})\n";
} else {
    echo "Existing Lead: Rahul (ID: {$lead->id})\n";
}

echo "--- 4. Checking Audio Duration & File ---\n";
$audioFile = 'leadger_demo_call.mp3';
$audioPath = public_path('assets/uploads/recordings/' . $audioFile);
$duration = 52; // Default 52 seconds

if (file_exists($audioPath)) {
    // If ffprobe / ffmpeg can read duration
    $ffprobe = shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 \"{$audioPath}\" 2>&1");
    if ($ffprobe && is_numeric(trim($ffprobe))) {
        $duration = (int)round((float)trim($ffprobe));
    }
}
echo "Audio File: {$audioFile}, Duration: {$duration}s\n";

echo "--- 5. Loading Transcript and AI Analysis ---\n";
$transcriptPath = base_path('leadger_demo_transcript.txt');
$transcript = file_exists($transcriptPath) ? file_get_contents($transcriptPath) : "Agent:\nHello, this is Akshitha calling from LeadGER. Am I speaking with Mr. Rahul?\n\nCustomer:\nYes, this is Rahul. How can I help you?\n\nAgent:\nI'm calling regarding your home loan inquiry. We have an option with a competitive interest rate and flexible repayment period.\n\nCustomer:\nOkay, I'm interested. Please tell me the eligibility and documents required.\n\nAgent:\nSure. We need identity proof, address proof, income documents, and recent bank statements. I can arrange a follow-up call tomorrow afternoon to explain the details.\n\nCustomer:\nThat works for me. Please call me tomorrow at three PM.\n\nAgent:\nPerfect. I have scheduled the follow-up for tomorrow at three PM. Thank you for your time.\n\nCustomer:\nThank you. I'll be available.";

$aiJsonPath = base_path('leadger_demo_ai_analysis.json');
$aiData = [];
if (file_exists($aiJsonPath)) {
    $aiData = json_decode(file_get_contents($aiJsonPath), true);
}

$summary = $aiData['summary'] ?? "Customer Rahul inquired about home loan options. Agent Akshitha explained competitive interest rates and required documents (ID proof, address proof, income documents, bank statements). Customer expressed positive interest and scheduled a follow-up call for tomorrow at 3:00 PM.";
$sentiment = $aiData['sentiment'] ?? "Positive";
$intent = $aiData['intent'] ?? "Interested";
$disposition = $aiData['disposition'] ?? "Follow-up";
$followUpDate = Carbon::tomorrow()->setHour(15)->setMinute(0)->setSecond(0);

$keyPointsText = is_array($aiData['key_points'] ?? null)
    ? implode("\n", array_map(fn($p) => "• " . $p, $aiData['key_points']))
    : ($aiData['key_points'] ?? "• Customer Rahul confirmed interest in home loan options.\n• Discussed competitive interest rate and flexible repayment period.\n• Document checklist provided: ID proof, address proof, income documents, and recent bank statements.\n• Follow-up call agreed and scheduled for tomorrow at 3:00 PM.");

$emotionAnalysisJson = json_encode($aiData['emotion_analysis'] ?? [
    'customer_emotion' => 'Interested & Receptive',
    'agent_tone' => 'Professional, Courteous & Helpful',
    'engagement_level' => 'High',
    'confidence_score' => '96%'
], JSON_PRETTY_PRINT);

$qualityFlagsJson = json_encode($aiData['quality_flags'] ?? [
    'greeting_proper' => 'Passed - Verified customer name & stated company name',
    'clear_articulation' => 'Passed - Clear, polite and well-paced delivery',
    'objection_handling' => 'Passed - Direct interest handled smoothly',
    'compliance_adherence' => 'Passed - Standard verification followed',
    'call_outcome' => 'Passed - Follow-up confirmed for 3:00 PM'
], JSON_PRETTY_PRINT);

echo "--- 6. Creating CallLog Record ---\n";
// Check if demo call log already exists
$callLog = CallLog::where('recording', $audioFile)->first();

if (!$callLog) {
    $callLog = new CallLog();
    $callLog->phonenumber = $lead->phonenumber;
    $callLog->call_type = 'click2call';
    $callLog->lead_id = $lead->id;
    $callLog->duration = $duration;
    $callLog->recording = $audioFile;
    $callLog->current_status = null;
    $callLog->next_status = 14;
    $callLog->remarks = '[DEMO CALL RECORDING] Home Loan inquiry - Follow-up tomorrow at 3:00 PM';
    $callLog->follow_up_on = $followUpDate;
    $callLog->created_by = $agent->id;
    $callLog->created_at = Carbon::now();
    $callLog->updated_at = Carbon::now();
    $callLog->verified = 1;
    $callLog->save();
    echo "Created CallLog ID: {$callLog->id}\n";
} else {
    $callLog->lead_id = $lead->id;
    $callLog->phonenumber = $lead->phonenumber;
    $callLog->duration = $duration;
    $callLog->remarks = '[DEMO CALL RECORDING] Home Loan inquiry - Follow-up tomorrow at 3:00 PM';
    $callLog->follow_up_on = $followUpDate;
    $callLog->created_by = $agent->id;
    $callLog->save();
    echo "Updated CallLog ID: {$callLog->id}\n";
}

echo "--- 7. Creating CallAiAnalysis Record ---\n";
$analysis = CallAiAnalysis::where('call_id', $callLog->id)->first();

if (!$analysis) {
    $analysis = CallAiAnalysis::create([
        'company_id' => 1,
        'call_id' => $callLog->id,
        'recording_id' => $audioFile,
        'transcript' => $transcript,
        'summary' => $summary,
        'interest_level' => 'HIGH',
        'sentiment' => 'POSITIVE',
        'intent' => $intent,
        'key_points' => $keyPointsText,
        'next_action' => "Follow-up required: Yes. Follow-up time: Tomorrow 3:00 PM. Call customer with detailed home loan eligibility checklist and repayment calculations.",
        'follow_up_date' => $followUpDate,
        'auto_disposition' => $disposition,
        'status' => 'completed',
        'error_message' => null,
        'sarvam_request_id' => 'srv_demo_leadger_call_001',
        'audio_duration_seconds' => $duration,
        'ai_cost' => 0.0500,
        'emotion_analysis' => $emotionAnalysisJson,
        'quality_flags' => $qualityFlagsJson,
    ]);
    echo "Created CallAiAnalysis ID: {$analysis->id}\n";
} else {
    $analysis->update([
        'recording_id' => $audioFile,
        'transcript' => $transcript,
        'summary' => $summary,
        'interest_level' => 'HIGH',
        'sentiment' => 'POSITIVE',
        'intent' => $intent,
        'key_points' => $keyPointsText,
        'next_action' => "Follow-up required: Yes. Follow-up time: Tomorrow 3:00 PM. Call customer with detailed home loan eligibility checklist and repayment calculations.",
        'follow_up_date' => $followUpDate,
        'auto_disposition' => $disposition,
        'status' => 'completed',
        'sarvam_request_id' => 'srv_demo_leadger_call_001',
        'audio_duration_seconds' => $duration,
        'ai_cost' => 0.0500,
        'emotion_analysis' => $emotionAnalysisJson,
        'quality_flags' => $qualityFlagsJson,
    ]);
    echo "Updated CallAiAnalysis ID: {$analysis->id}\n";
}

echo "\n--- SUCCESS! Demo Call Recording, Transcript & AI Analysis are ready ---\n";
echo "Call ID: {$callLog->id}\n";
echo "Recording: {$callLog->recording}\n";
echo "Campaign: {$campaign->name}\n";
echo "Agent: {$agent->name}\n";
echo "Customer: {$lead->name}\n";
echo "Duration: {$duration}s\n";
echo "Analysis ID: {$analysis->id}\n";
