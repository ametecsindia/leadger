<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Lead;
use App\Models\CallLog;
use App\Models\CallAiAnalysis;

class AiCallAnalysisDemoRollback extends Seeder
{
    /**
     * Revert demo data and restore previous database state.
     *
     * @return void
     */
    public function run()
    {
        echo "Rolling back LeadGER AI Call Analysis Demo Data...\n";

        $prefix = DB::getTablePrefix();
        $aiTable = $prefix . 'call_ai_analyses';
        $aiBackupTable = $prefix . 'call_ai_analyses_backup';
        $logsBackupTable = $prefix . 'call_logs_demo_backup';

        // 1. Restore call_ai_analyses from backup if available
        if (Schema::hasTable('call_ai_analyses_backup')) {
            CallAiAnalysis::truncate();
            DB::statement("INSERT INTO {$aiTable} SELECT * FROM {$aiBackupTable}");
            echo " - Restored call_ai_analyses from backup table.\n";
        } else {
            // Delete target demo call analyses
            CallAiAnalysis::whereIn('call_id', [28914, 28913, 28889, 28876, 28861])->delete();
            echo " - Removed demo records from call_ai_analyses.\n";
        }

        // 2. Restore call_logs original values if available
        if (Schema::hasTable('call_logs_demo_backup')) {
            $backups = DB::table('call_logs_demo_backup')->get();
            foreach ($backups as $b) {
                $call = CallLog::find($b->call_id);
                if ($call) {
                    $call->lead_id = $b->original_lead_id;
                    $call->created_by = $b->original_created_by;
                    $call->duration = $b->original_duration;
                    $call->phonenumber = $b->original_phonenumber;
                    $call->save();
                }
            }
            echo " - Restored original call_logs fields.\n";
        }

        // 3. Delete demo leads
        $deletedLeads = Lead::where('description', 'DEMO_LEAD')->delete();
        echo " - Removed {$deletedLeads} demo lead records.\n";

        // 4. Delete demo users
        User::whereIn('email', [
            'rahul.demo@ametecsindia.com',
            'suresh.demo@ametecsindia.com'
        ])->delete();
        echo " - Removed demo users.\n";

        echo "\nRollback completed successfully! Real data restored.\n";
    }
}
