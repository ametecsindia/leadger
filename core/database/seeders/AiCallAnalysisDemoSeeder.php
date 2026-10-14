<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Lead;
use App\Models\Campaign;
use App\Models\CallLog;
use App\Models\CallAiAnalysis;
use App\Models\CompanyAiSetting;

class AiCallAnalysisDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo "Starting LeadGER AI Call Analysis Demo Data Seeder...\n";

        // 1. Ensure Company AI Settings exists and is enabled
        CompanyAiSetting::updateOrCreate(
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
                'key_points_enabled' => true,
                'next_action_enabled' => true,
                'auto_disposition_enabled' => true,
                'billing_mode' => 'Usage Based',
            ]
        );

        $prefix = DB::getTablePrefix();
        $aiTable = $prefix . 'call_ai_analyses';
        $aiBackupTable = $prefix . 'call_ai_analyses_backup';
        $logsBackupTable = $prefix . 'call_logs_demo_backup';

        // 2. Safe Backup Table Creation for Reversibility
        if (!Schema::hasTable('call_ai_analyses_backup')) {
            DB::statement("CREATE TABLE {$aiBackupTable} LIKE {$aiTable}");
        }
        DB::statement("INSERT IGNORE INTO {$aiBackupTable} SELECT * FROM {$aiTable}");

        if (!Schema::hasTable('call_logs_demo_backup')) {
            DB::statement("CREATE TABLE {$logsBackupTable} (
                call_id BIGINT PRIMARY KEY,
                original_lead_id BIGINT,
                original_created_by BIGINT,
                original_duration INT NULL,
                original_phonenumber VARCHAR(50) NULL
            )");
        }

        $targetCallIds = [28914, 28913, 28889, 28876, 28861];
        foreach ($targetCallIds as $cid) {
            $call = CallLog::find($cid);
            if ($call) {
                DB::table('call_logs_demo_backup')->updateOrInsert(
                    ['call_id' => $cid],
                    [
                        'original_lead_id' => $call->lead_id,
                        'original_created_by' => $call->created_by,
                        'original_duration' => $call->duration,
                        'original_phonenumber' => $call->phonenumber,
                    ]
                );
            }
        }

        // 3. Find or Create Required Demo Users: Akshitha, Rahul, Suresh
        $akshitha = User::where('name', 'like', '%Akshitha%')->first();
        if (!$akshitha) {
            $akshitha = User::create([
                'name' => 'Akshitha',
                'email' => 'akshitha.demo@ametecsindia.com',
                'password' => bcrypt('password123'),
                'permissions_id' => 7,
                'status' => 1,
                'team_id' => 1,
            ]);
        }

        $rahul = User::where('name', 'Rahul')->first();
        if (!$rahul) {
            $rahul = User::create([
                'name' => 'Rahul',
                'email' => 'rahul.demo@ametecsindia.com',
                'password' => bcrypt('password123'),
                'permissions_id' => 7,
                'status' => 1,
                'team_id' => 1,
            ]);
        }

        $suresh = User::where('name', 'Suresh')->first();
        if (!$suresh) {
            $suresh = User::create([
                'name' => 'Suresh',
                'email' => 'suresh.demo@ametecsindia.com',
                'password' => bcrypt('password123'),
                'permissions_id' => 7,
                'status' => 1,
                'team_id' => 1,
            ]);
        }

        // 4. Ensure Valid Campaigns for Filter Testing
        $campaign1 = Campaign::find(1) ?? Campaign::first();
        $campaign2 = Campaign::find(2) ?? Campaign::skip(1)->first() ?? $campaign1;

        $c1_id = $campaign1 ? $campaign1->id : 1;
        $c2_id = $campaign2 ? $campaign2->id : $c1_id;

        // 5. Create or Update Demo Leads with Customer Names
        $leadRavi = Lead::updateOrCreate(
            ['email' => 'ravi.kumar.demo@horizonrealty.com'],
            [
                'name' => 'Ravi Kumar',
                'phonenumber' => '9876543210',
                'company' => 'Horizon Realty',
                'campaign_id' => $c1_id,
                'status' => 1,
                'description' => 'DEMO_LEAD',
                'created_by' => $akshitha->id,
            ]
        );

        $leadPriya = Lead::updateOrCreate(
            ['email' => 'priya.shah.demo@apexsolutions.com'],
            [
                'name' => 'Priya Shah',
                'phonenumber' => '9876543211',
                'company' => 'Apex Solutions',
                'campaign_id' => $c1_id,
                'status' => 1,
                'description' => 'DEMO_LEAD',
                'created_by' => $rahul->id,
            ]
        );

        $leadAnil = Lead::updateOrCreate(
            ['email' => 'anil.kumar.demo@metrodist.com'],
            [
                'name' => 'Anil Kumar',
                'phonenumber' => '9876543212',
                'company' => 'Metro Distribution',
                'campaign_id' => $c1_id,
                'status' => 1,
                'description' => 'DEMO_LEAD',
                'created_by' => $suresh->id,
            ]
        );

        $leadMeena = Lead::updateOrCreate(
            ['email' => 'meena.reddy.demo@sunrisefinance.com'],
            [
                'name' => 'Meena Reddy',
                'phonenumber' => '9876543213',
                'company' => 'Sunrise Finance',
                'campaign_id' => $c2_id,
                'status' => 1,
                'description' => 'DEMO_LEAD',
                'created_by' => $akshitha->id,
            ]
        );

        $leadKiran = Lead::updateOrCreate(
            ['email' => 'kiran.rao.demo@zenithlogistics.com'],
            [
                'name' => 'Kiran Rao',
                'phonenumber' => '9876543214',
                'company' => 'Zenith Logistics',
                'campaign_id' => $c2_id,
                'status' => 1,
                'description' => 'DEMO_LEAD',
                'created_by' => $rahul->id,
            ]
        );

        // 6. Demo Records Specification
        $demoRecords = [
            [
                'call_id' => 28914,
                'user' => $akshitha,
                'lead' => $leadRavi,
                'duration_seconds' => 252, // 4.2 mins
                'interest' => 'HIGH',
                'sentiment' => 'POSITIVE',
                'intent' => 'Product Enquiry',
                'status' => 'completed',
                'created_at' => '2026-03-06 04:19:09',
                'summary' => 'Customer Ravi Kumar inquired about LeadGER Enterprise CRM and auto-dialer capabilities for a team of 45 sales agents. Akshitha walked through the Smart31 WebRTC dialer, real-time call tracking, and AI transcript analytics. Customer was impressed with the browser-based calling without hardware setup.',
                'key_points' => "• Inquired about LeadGER Enterprise plan for 45 agents.\n• Highly interested in Smart31 WebRTC in-browser calling and AI transcription.\n• Requested enterprise pricing proposal with volume discounts.\n• Target decision timeline: End of next week.",
                'next_action' => 'Email Enterprise proposal PDF with SLA tier options and schedule onboarding demo for Thursday at 3 PM.',
                'auto_disposition' => 'Hot Lead / Enterprise Demo Scheduled',
                'transcript' => "Agent: Good morning! Thank you for calling LeadGER Support. My name is Akshitha, how may I assist you today?\nCustomer: Hi Akshitha, I'm Ravi Kumar from Horizon Realty. We're looking to upgrade our telecalling infrastructure and CRM system.\nAgent: Welcome Mr. Kumar! We'd be glad to help. How many telecallers are currently in your team?\nCustomer: We have about 45 outbound telecallers handling property leads across three regional branches.\nAgent: Excellent. LeadGER is built specifically for high-velocity teams like yours. Our Smart31 WebRTC engine runs directly in the browser—meaning zero SIP hardware setup, instant click-to-call, and automated lead distribution.\nCustomer: That sounds very convenient. Does LeadGER also provide call recordings and automatic transcription for compliance?\nAgent: Yes, absolutely! Every call recording is encrypted and automatically analyzed with Sarvam AI to produce instant transcripts, sentiment tagging, and customer interest scores.\nCustomer: That is fantastic. Can you send over an enterprise commercial quote and arrange a live product demonstration for our management?\nAgent: Certainly! I will prepare the formal enterprise quotation right away and book your demo session for this Thursday at 3 PM.\nCustomer: Sounds wonderful, Akshitha. Thank you for your assistance.\nAgent: You're welcome Mr. Kumar! Have a great day ahead."
            ],
            [
                'call_id' => 28913,
                'user' => $rahul,
                'lead' => $leadPriya,
                'duration_seconds' => 210, // 3.5 mins
                'interest' => 'MEDIUM',
                'sentiment' => 'NEUTRAL',
                'intent' => 'Support',
                'status' => 'completed',
                'created_at' => '2026-03-05 12:20:39',
                'summary' => 'Customer Priya Shah contacted support regarding API webhook configuration for syncing dispositions to their third-party billing platform. Rahul guided her through the webhook signature setup and tested the endpoint successfully.',
                'key_points' => "• Customer encountered webhook timeout on custom endpoint.\n• Rahul diagnosed TLS handshake issue on customer's receiver server.\n• Configured webhook retry policy and verified delivery with test payload.\n• Advised on standard payload schemas for call end events.",
                'next_action' => 'Monitor webhook delivery logs for 48 hours and send technical documentation link.',
                'auto_disposition' => 'Support Resolved / Webhook Configured',
                'transcript' => "Agent: Hello! LeadGER Technical Support, Rahul speaking. How can I help you today?\nCustomer: Hi Rahul, this is Priya Shah from Apex Solutions. We're having an issue configuring the webhook for call dispositions.\nAgent: Hello Ms. Shah! I'd be happy to assist with your webhook setup. Are you seeing an HTTP response code or a connection timeout?\nCustomer: Our server logs show a connection timeout whenever a telecaller finishes a call and saves a disposition.\nAgent: Let me check your webhook endpoint settings in our dashboard. Could you confirm your registered domain?\nCustomer: It's api.apexsolutions.co.in/webhooks/leadger.\nAgent: Thank you. I see the endpoint. It looks like the timeout was due to an SSL handshake delay on port 8443. Let's send a test ping now.\nCustomer: One second... Yes! I just received the test JSON payload with HTTP 200 OK.\nAgent: Perfect! I've also enabled the automated 3-attempt retry policy in case of any temporary network drops.\nCustomer: That is very helpful, Rahul. Everything is working smoothly now.\nAgent: Glad to help, Ms. Shah! Feel free to reach out if you need anything else."
            ],
            [
                'call_id' => 28889,
                'user' => $suresh,
                'lead' => $leadAnil,
                'duration_seconds' => 168, // 2.8 mins
                'interest' => 'LOW',
                'sentiment' => 'NEGATIVE',
                'intent' => 'Complaint',
                'status' => 'processing',
                'created_at' => '2025-09-29 06:37:15',
                'summary' => 'Customer Anil Kumar called to report occasional audio dropouts during peak outbound hours. Suresh logged the incident with the telecom gateway provider and initiated diagnostic packet tracing. The audio recording is currently being processed by the AI engine.',
                'key_points' => "• Customer reported jitter during peak calling between 11 AM - 1 PM.\n• Suresh verified local network ISP speed and gateway route health.\n• Priority ticket #LGT-4482 created with VoIP carrier team.\n• Audio undergoing automated speech-to-text processing.",
                'next_action' => 'Follow up with telecom carrier engineers and provide incident status update within 3 hours.',
                'auto_disposition' => 'Escalated / Technical Investigation',
                'transcript' => "Agent: Good afternoon, LeadGER Customer Care, Suresh speaking. How can I assist you?\nCustomer: Hi Suresh. We've been having continuous audio cut-offs on outbound calls since this morning. This is impacting our callers severely.\nAgent: I sincerely apologize for the inconvenience, Mr. Kumar. Let me check the gateway latency logs for your account right now.\nCustomer: It's happening particularly when our callers dial mobile numbers in Mumbai circle.\nAgent: Understood. I see a latency spike on carrier route 4. I am immediately rerouting your team traffic to our secondary Tier-1 telecom gateway.\nCustomer: Please make sure this doesn't happen again. We have client SLA commitments.\nAgent: Absolutely, Mr. Kumar. The failover route is active right now, and I have raised priority incident ticket LGT-4482 with our infrastructure lead.\nCustomer: Okay. Let me know once the route is completely stabilized.\nAgent: I will personally call you back with a confirmation within two hours. Thank you for your patience."
            ],
            [
                'call_id' => 28876,
                'user' => $akshitha,
                'lead' => $leadMeena,
                'duration_seconds' => 306, // 5.1 mins
                'interest' => 'HIGH',
                'sentiment' => 'POSITIVE',
                'intent' => 'Follow-up',
                'status' => 'completed',
                'created_at' => '2025-09-28 10:15:00',
                'summary' => 'Follow-up call with Meena Reddy from Sunrise Finance. Client confirmed internal executive committee approval for onboarding 30 agent seats on the annual billing plan with Sarvam AI Call Intelligence package.',
                'key_points' => "• Executive board approved annual contract for 30 user licenses.\n• Client specifically requested the Sarvam AI Call Intelligence add-on for QA monitoring.\n• Invoiced under corporate GST with 18% tax deduction details.\n• Target go-live date set for the 1st of next month.",
                'next_action' => 'Dispatch Master Services Agreement (MSA) and initiate staging account provisioning.',
                'auto_disposition' => 'Deal Closed / Contract Sent',
                'transcript' => "Agent: Hello Ms. Meena! This is Akshitha following up from LeadGER as discussed last Monday.\nCustomer: Hi Akshitha! Good to hear from you. I actually have great news—our executive committee met yesterday and approved the proposal!\nAgent: That is wonderful news, Ms. Meena! We are thrilled to welcome Sunrise Finance to LeadGER.\nCustomer: We will be starting with 30 agent licenses and definitely want the AI Call Intelligence module included for our QA managers.\nAgent: Excellent choice! The AI Call Intelligence package will give your QA managers instant visibility into sentiment trends, keywords, and agent talk-time ratios.\nCustomer: Yes, that was the deciding factor for our compliance director. Can you email the formal contract and invoice today?\nAgent: Absolutely. I'll send over the Master Services Agreement and invoice with your company's GST details within the next hour.\nCustomer: Perfect. Once signed, we can schedule our admin onboarding session for Friday.\nAgent: Sounds like a plan, Ms. Meena. Congratulations on getting the sign-off, and thank you for choosing LeadGER!\nCustomer: Thank you so much, Akshitha! Looking forward to working together."
            ],
            [
                'call_id' => 28861,
                'user' => $rahul,
                'lead' => $leadKiran,
                'duration_seconds' => 114, // 1.9 mins
                'interest' => 'MEDIUM',
                'sentiment' => 'NEUTRAL',
                'intent' => 'Pricing',
                'status' => 'pending',
                'created_at' => '2025-09-27 16:45:00',
                'summary' => 'Prospective customer Kiran Rao called to compare LeadGER pricing tiers against competitors. Rahul explained per-seat monthly billing vs annual discount options. Call is queued for automated AI speech transcription.',
                'key_points' => "• Prospect evaluating LeadGER Professional tier for 15 telecallers.\n• Inquired about discounts on annual prepayment.\n• Requested comparison matrix between cloud SIP vs WebRTC dialer.\n• Analysis status is pending automated speech processing.",
                'next_action' => 'Send comparison brochure and schedule follow-up call on Friday.',
                'auto_disposition' => 'Quote Requested / Pending AI Analysis',
                'transcript' => "Agent: Hello, thank you for contacting LeadGER. My name is Rahul, how can I assist you today?\nCustomer: Hi Rahul, I'm calling from Zenith Logistics. We're looking into outbound calling software and wanted to understand your pricing model.\nAgent: Hi Mr. Rao! We offer flexible monthly billing per agent seat as well as an annual plan with a 20% discount. How many seats are you looking to activate?\nCustomer: We have about 15 agents right now. Do you charge extra for call recording storage?\nAgent: Not at all! Cloud audio recording storage and basic analytics are included across all standard tiers.\nCustomer: Great. Could you email me a detailed comparison sheet of your Professional plan vs Enterprise plan?\nAgent: Certainly! What is your preferred email address, Mr. Rao?\nCustomer: Please send it to kiran.rao@zenithlogistics.in.\nAgent: Done! I will email the pricing sheet and brochure immediately.\nCustomer: Thank you, Rahul. I'll review it and get back to you."
            ],
        ];

        // 7. Clear non-demo seed records from call_ai_analyses to present the exact 5 clean demo records
        CallAiAnalysis::whereNotIn('call_id', $targetCallIds)->delete();

        foreach ($demoRecords as $row) {
            // Update or create CallLog
            $callLog = CallLog::find($row['call_id']);
            if (!$callLog) {
                $callLog = new CallLog();
                $callLog->id = $row['call_id'];
            }
            $callLog->created_by = $row['user']->id;
            $callLog->lead_id = $row['lead']->id;
            $callLog->duration = $row['duration_seconds'];
            $callLog->phonenumber = $row['lead']->phonenumber;
            $callLog->created_at = $row['created_at'];
            $callLog->updated_at = now();
            $callLog->save();

            // Calculate sample AI cost (~0.15 INR / min)
            $cost = round(($row['duration_seconds'] / 60) * 0.15, 4);

            // Populate CallAiAnalysis
            CallAiAnalysis::updateOrCreate(
                ['call_id' => $row['call_id']],
                [
                    'company_id' => 1,
                    'recording_id' => 'rec_' . $row['call_id'] . '.wav',
                    'transcript' => $row['transcript'],
                    'summary' => $row['summary'],
                    'interest_level' => $row['interest'],
                    'sentiment' => $row['sentiment'],
                    'intent' => $row['intent'],
                    'key_points' => $row['key_points'],
                    'next_action' => $row['next_action'],
                    'follow_up_date' => now()->addDays(2),
                    'auto_disposition' => $row['auto_disposition'],
                    'status' => $row['status'],
                    'error_message' => null,
                    'sarvam_request_id' => 'srv_req_' . strtoupper(substr(md5($row['call_id']), 0, 10)),
                    'audio_duration_seconds' => $row['duration_seconds'],
                    'ai_cost' => $cost,
                    'created_at' => $row['created_at'],
                    'updated_at' => now(),
                ]
            );

            echo " - Seeded Demo Call #{$row['call_id']}: User {$row['user']->name}, Customer {$row['lead']->name}, Status: {$row['status']}\n";
        }

        echo "\nDemo data seeded successfully!\n";
    }
}
