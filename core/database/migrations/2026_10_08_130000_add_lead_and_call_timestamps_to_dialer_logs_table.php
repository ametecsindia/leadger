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
        if (Schema::hasTable('dialer_logs')) {
            Schema::table('dialer_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('dialer_logs', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->after('campaign_id')->index();
                }
                if (!Schema::hasColumn('dialer_logs', 'start_time')) {
                    $table->timestamp('start_time')->nullable()->after('call_state');
                }
                if (!Schema::hasColumn('dialer_logs', 'answer_time')) {
                    $table->timestamp('answer_time')->nullable()->after('start_time');
                }
                if (!Schema::hasColumn('dialer_logs', 'end_time')) {
                    $table->timestamp('end_time')->nullable()->after('answer_time');
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
        if (Schema::hasTable('dialer_logs')) {
            Schema::table('dialer_logs', function (Blueprint $table) {
                $columns = ['lead_id', 'start_time', 'answer_time', 'end_time'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('dialer_logs', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
