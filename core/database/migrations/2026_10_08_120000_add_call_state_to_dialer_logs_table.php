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
                if (!Schema::hasColumn('dialer_logs', 'call_state')) {
                    $table->string('call_state', 50)->nullable()->default('initiated')->after('call_type');
                }
                if (Schema::hasColumn('dialer_logs', 'campaign_id')) {
                    $table->unsignedBigInteger('campaign_id')->nullable()->change();
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'daily_target')) {
                    $table->integer('daily_target')->nullable()->default(50)->after('status');
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
                if (Schema::hasColumn('dialer_logs', 'call_state')) {
                    $table->dropColumn('call_state');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'daily_target')) {
                    $table->dropColumn('daily_target');
                }
            });
        }
    }
};
