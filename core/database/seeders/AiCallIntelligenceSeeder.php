<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CallLog;
use App\Models\CallAiAnalysis;
use App\Models\AiUsageLog;
use App\Models\CompanyAiSetting;

class AiCallIntelligenceSeeder extends Seeder
{
    public function run()
    {
        // Ensure Company Settings exists
        CompanyAiSetting::firstOrCreate(
            ['company_id' => 1],
            [
                'ai_enabled' => true,
                'provider' => 'Sarvam AI',
                'auto_analysis_enabled' => true,
                'transcript_enabled' => true,
                'summary_enabled' => true,
                'interest_enabled' => true,
                'sentiment_enabled' => true,
                'intent_enabled' => true,
                'key_points' => true,
                'next_action_enabled' => true,
                'auto_disposition_enabled' => true,
                'billing_mode' => 'Usage Based',
            ]
        );

        // Fetch last 15 call logs with non-empty recordings
        $callLogs = CallLog::whereNotNull('duration')
            ->where('duration', '>', 0)
            ->orderBy('id', 'desc')
            ->take(15)
            ->get();

        $sampleData = [
            [
                'interest' => 'HIGH',
                'sentiment' => 'POSITIVE',
                'intent' => 'Product Demo & Pricing Request',
                'summary' => 'Customer inquired about LeadGER Enterprise plan features, custom CRM integration, and volume pricing for 50 telecallers. Agent explained Smart31 WebRTC integration and shared trial proposal details.',
                'key_points' => "• Customer requested live demo for team of 50 telecallers.\n• Expressed strong interest in WebRTC auto-dialer feature.\n• Asked for official commercial quote by tomorrow 2 PM.\n• Existing contract with legacy provider expires end of month.",
                'next_action' => 'Send Enterprise Proposal PDF & schedule technical onboarding session.',
                'auto_disposition' => 'Hot Lead / Demo Scheduled',
                'transcript' => "Agent: Hello! Thank you for calling LeadGER Support. How may I help you today?\nCustomer: Hi, I'm calling from Acme Corp. We are evaluating CRM and auto-dialer solutions for our sales team.\nAgent: Excellent! LeadGER offers integrated WebRTC calling, click-to-call, and AI call intelligence. How many telecallers are in your team?\nCustomer: We currently have 50 active telecallers. Does your platform support CRM lead distribution?\nAgent: Yes, absolutely. We support instant lead upload, automatic assignment, and real-time call analytics.\nCustomer: Perfect. Can you send a detailed commercial proposal and schedule a demo for tomorrow?\nAgent: Certainly! I will email the proposal right away and book your demo session.",
                'status' => 'completed',
            ],
            [
                'interest' => 'HIGH',
                'sentiment' => 'POSITIVE',
                'intent' => 'Renewal & Upgrade Enquiry',
                'summary' => 'Existing client looking to renew annual license and add Sarvam AI Call Intelligence module. Discussed billing parameters and feature activation timeline.',
                'key_points' => "• Client satisfied with current call connectivity.\n• Wants auto-transcription for compliance audits.\n• Confirmed budget approval for annual renewal.\n• Requested invoice under GST registered company name.",
                'next_action' => 'Generate Tax Invoice with Sarvam AI add-on.',
                'auto_disposition' => 'Renewal Confirmed / Up-sell',
                'transcript' => "Agent: Good afternoon! LeadGER Account Management team. How can I assist you?\nCustomer: Hi, our current annual subscription is up for renewal next week. We also noticed the new AI Call Intelligence feature.\nAgent: Great to hear! The AI Call Intelligence module automatically generates call transcripts, sentiment analysis, and call summaries using Sarvam AI.\nCustomer: That sounds ideal for our quality assurance audits. Please include it in our renewal invoice.\nAgent: Will do! Sending updated proforma invoice to your registered email.",
                'status' => 'completed',
            ],
            [
                'interest' => 'MEDIUM',
                'sentiment' => 'NEUTRAL',
                'intent' => 'Follow-up on Previous Quote',
                'summary' => 'Customer reviewed initial pricing brochure. Requested comparison breakdown between Cloud SIP Trunk vs WebRTC setup before making final decision.',
                'key_points' => "• Prospect is comparing LeadGER with competitor solutions.\n• Needs clarification on setup fees and hardware requirements.\n• Decision pending internal management review on Friday.",
                'next_action' => 'Send WebRTC vs SIP comparison chart & call back Friday morning.',
                'auto_disposition' => 'Follow-up Scheduled',
                'transcript' => "Agent: Hello, calling from LeadGER sales team to follow up on your enquiry.\nCustomer: Hi, thanks for checking in. We reviewed your pricing table, but we need clarification on WebRTC vs SIP trunking.\nAgent: WebRTC works directly in the web browser without any local hardware, whereas SIP connects to your PBX server.\nCustomer: Got it. Let me discuss this with our IT head and get back to you by Friday.\nAgent: Sounds good! I'll drop a comparison document in your inbox.",
                'status' => 'completed',
            ],
            [
                'interest' => 'LOW',
                'sentiment' => 'NEGATIVE',
                'intent' => 'Not Interested / Wrong Number',
                'summary' => 'Customer stated they already purchased an alternative dialer software last month and are not looking for new CRM software.',
                'key_points' => "• Prospect signed 1-year contract with third-party vendor.\n• Requested removal from active outbound campaign queue.",
                'next_action' => 'Update lead status to DND / Not Interested.',
                'auto_disposition' => 'Not Interested',
                'transcript' => "Agent: Hello, am I speaking with Mr. Rajesh?\nCustomer: Yes, but we already bought another calling software last month. Please don't call again.\nAgent: Understood, sir. Updating your preferences right away. Have a good day.",
                'status' => 'completed',
            ],
            [
                'interest' => 'HIGH',
                'sentiment' => 'POSITIVE',
                'intent' => 'Technical Support & API Setup',
                'summary' => 'Client requested assistance configuring webhook URL for post-call status synchronization.',
                'key_points' => "• Technical query regarding lead status webhooks.\n• Agent guided client step-by-step through API Settings panel.\n• Webhook test ping verified successfully.",
                'next_action' => 'Mark support ticket as resolved.',
                'auto_disposition' => 'Support Resolved',
                'transcript' => "Agent: LeadGER Technical Support, how may I help you?\nCustomer: Hi, we're trying to configure our webhook endpoint for call logs.\nAgent: Sure, please navigate to Settings -> System Settings -> Webhook API and paste your callback URL.\nCustomer: Done! The test ping succeeded immediately. Thank you so much!\nAgent: You're very welcome!",
                'status' => 'completed',
            ],
            [
                'interest' => 'MEDIUM',
                'sentiment' => 'NEUTRAL',
                'intent' => 'Processing Request',
                'summary' => 'Recording was queued for AI analysis. Sarvam Speech-to-Text processing in progress.',
                'key_points' => "• Batch job initialized.\n• Audio length 184 seconds.\n• Status: Processing.",
                'next_action' => 'Await Sarvam webhook execution.',
                'auto_disposition' => 'Pending AI Analysis',
                'transcript' => "Call audio is currently being processed by Sarvam AI Speech Engine...",
                'status' => 'processing',
            ],
            [
                'interest' => 'LOW',
                'sentiment' => 'NEUTRAL',
                'intent' => 'Processing Failed',
                'summary' => 'AI Analysis failed due to audio file corruption or network latency timeout.',
                'key_points' => "• Error: Audio sample rate invalid or unreachable recording storage URL.\n• Request ID: srv_err_99214.",
                'next_action' => 'Click Retry button to re-trigger AI analysis job.',
                'auto_disposition' => 'AI Failed / Retry Required',
                'transcript' => "Transcript unavailable due to processing error.",
                'status' => 'failed',
                'error_message' => 'Sarvam API Timeout: Unable to fetch audio payload from recording URL.',
            ]
        ];

        $idx = 0;
        foreach ($callLogs as $call) {
            $data = $sampleData[$idx % count($sampleData)];
            $duration = max($call->duration ?? 120, 45);
            $cost = round(($duration / 60) * 0.15, 4); // ~0.15 INR per minute

            $analysis = CallAiAnalysis::updateOrCreate(
                ['call_id' => $call->id],
                [
                    'company_id' => 1,
                    'recording_id' => $call->recording ?? ('rec_' . $call->id . '.wav'),
                    'transcript' => $data['transcript'],
                    'summary' => $data['summary'],
                    'interest_level' => $data['interest'],
                    'sentiment' => $data['sentiment'],
                    'intent' => $data['intent'],
                    'key_points' => $data['key_points'],
                    'next_action' => $data['next_action'],
                    'follow_up_date' => now()->addDays(2),
                    'auto_disposition' => $data['auto_disposition'],
                    'status' => $data['status'],
                    'error_message' => $data['error_message'] ?? null,
                    'sarvam_request_id' => 'srv_req_' . strtoupper(substr(md5($call->id), 0, 10)),
                    'audio_duration_seconds' => $duration,
                    'ai_cost' => $cost,
                    'created_at' => $call->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            // Log usage
            AiUsageLog::updateOrCreate(
                ['call_id' => $call->id],
                [
                    'company_id' => 1,
                    'recording_id' => $call->recording ?? ('rec_' . $call->id . '.wav'),
                    'provider' => 'Sarvam AI',
                    'service' => 'Speech-to-Text & Call Intelligence',
                    'audio_duration_seconds' => $duration,
                    'usage_amount' => $cost,
                    'currency' => 'INR',
                    'status' => ($data['status'] === 'failed') ? 'failed' : 'completed',
                    'created_at' => $call->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            $idx++;
        }
    }
}
