<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('transcription_jobs')) {
            Schema::table('transcription_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('transcription_jobs', 'billing_status')) {
                    $table->string('billing_status', 30)->default('PENDING')->after('status')->index();
                }
                if (!Schema::hasColumn('transcription_jobs', 'actual_duration_seconds')) {
                    $table->integer('actual_duration_seconds')->default(0)->after('duration_seconds');
                }
                if (!Schema::hasColumn('transcription_jobs', 'actual_billable_minutes')) {
                    $table->decimal('actual_billable_minutes', 10, 2)->default(0.00)->after('billable_minutes');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('transcription_jobs')) {
            Schema::table('transcription_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('transcription_jobs', 'billing_status')) {
                    $table->dropColumn('billing_status');
                }
                if (Schema::hasColumn('transcription_jobs', 'actual_duration_seconds')) {
                    $table->dropColumn('actual_duration_seconds');
                }
                if (Schema::hasColumn('transcription_jobs', 'actual_billable_minutes')) {
                    $table->dropColumn('actual_billable_minutes');
                }
            });
        }
    }
};
