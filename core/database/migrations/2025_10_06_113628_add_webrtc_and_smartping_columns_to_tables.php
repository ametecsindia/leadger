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
  public function up(): void
    {
        // Add webrtc_call_id to call_logs table
        Schema::table('call_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('call_logs', 'webrtc_call_id')) {
                $table->string('webrtc_call_id')->nullable();
            }
        });

        // Add webrtc_call_id to dialer_logs table
        Schema::table('dialer_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('dialer_logs', 'webrtc_call_id')) {
                $table->string('webrtc_call_id')->nullable();
            }
        });
        Schema::table('obd_did', function (Blueprint $table) {
            if (!Schema::hasColumn('obd_did', 'webrtc')) {
                $table->string('webrtc')->default('0');
            }
        });
        // Add smartping and webrtc related columns to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'smartping_user_id')) {
                $table->string('smartping_user_id')->nullable();
            }
            if (!Schema::hasColumn('users', 'smartping_password')) {
                $table->string('smartping_password')->nullable()->after('smartping_user_id');
            }
            if (!Schema::hasColumn('users', 'mobile')) {
                $table->string('mobile', 20)->nullable()->after('smartping_password');
            }
            if (!Schema::hasColumn('users', 'webrtc_default_number')) {
                $table->string('webrtc_default_number')->nullable()->after('mobile');
            }
            if (!Schema::hasColumn('users', 'webrtc_channel')) {
                $table->string('webrtc_channel')->default('0')->after('webrtc_default_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
     
    }
};
