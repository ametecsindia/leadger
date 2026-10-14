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
        if (!Schema::hasTable('recordings')) {
            Schema::create('recordings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('provider_call_id', 191)->unique(); // Unique key for idempotency
                $table->string('call_id', 191)->nullable()->index();
                $table->unsignedBigInteger('dialer_log_id')->nullable()->index();
                $table->unsignedBigInteger('call_log_id')->nullable()->index();
                $table->tinyInteger('source_code')->default(2); // 2 = Dialing (dialer_logs), 0 = Campaign Calling (call_logs)
                $table->string('source_type', 50)->default('Dialing');
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('agent_name', 150)->nullable();
                $table->unsignedBigInteger('lead_id')->nullable()->index();
                $table->string('customer_name', 150)->nullable();
                $table->string('phone_number', 50)->index();
                $table->unsignedBigInteger('campaign_id')->nullable()->index();
                $table->string('campaign_name', 150)->nullable();
                $table->timestamp('call_date')->nullable();
                $table->integer('duration')->default(0); // Talk duration in seconds
                $table->text('recording_url')->nullable();
                $table->text('file_path')->nullable();
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('download_status', 30)->default('pending'); // pending, downloading, completed, failed
                $table->integer('download_attempts')->default(0);
                $table->string('call_status', 50)->default('connected');
                $table->longText('raw_payload')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('dialer_logs')) {
            Schema::table('dialer_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('dialer_logs', 'provider_call_id')) {
                    $table->string('provider_call_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('dialer_logs', 'recording_id')) {
                    $table->unsignedBigInteger('recording_id')->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('call_logs')) {
            Schema::table('call_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('call_logs', 'provider_call_id')) {
                    $table->string('provider_call_id', 191)->nullable()->index();
                }
                if (!Schema::hasColumn('call_logs', 'recording_id')) {
                    $table->unsignedBigInteger('recording_id')->nullable()->index();
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
                if (Schema::hasColumn('dialer_logs', 'recording_id')) {
                    $table->dropColumn('recording_id');
                }
                if (Schema::hasColumn('dialer_logs', 'provider_call_id')) {
                    $table->dropColumn('provider_call_id');
                }
            });
        }

        if (Schema::hasTable('call_logs')) {
            Schema::table('call_logs', function (Blueprint $table) {
                if (Schema::hasColumn('call_logs', 'recording_id')) {
                    $table->dropColumn('recording_id');
                }
                if (Schema::hasColumn('call_logs', 'provider_call_id')) {
                    $table->dropColumn('provider_call_id');
                }
            });
        }

        Schema::dropIfExists('recordings');
    }
};
