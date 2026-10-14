<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('blocked_call_logs')) {
            Schema::create('blocked_call_logs', function (Blueprint $table) {
                $table->id();
                $table->string('phone_number', 50);
                $table->string('phone_normalized', 30)->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('agent_name', 191)->nullable();
                $table->string('reason', 50);
                $table->string('message', 255);
                $table->string('channel', 50)->nullable();
                $table->dateTime('blocked_at')->index();
                $table->timestamps();

                $table->index(['phone_normalized', 'blocked_at'], 'idx_blocked_phone_time');
            });
        }

        if (!Schema::hasTable('daily_phone_call_attempts')) {
            Schema::create('daily_phone_call_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('phone_normalized', 30);
                $table->date('call_date')->index();
                $table->unsignedInteger('attempt_count')->default(0);
                $table->timestamps();

                $table->unique(['phone_normalized', 'call_date'], 'uniq_phone_call_date');
            });
        }

        // Ensure call_logs and dialer_logs have index on (phone, created_at)
        if (Schema::hasTable('call_logs')) {
            Schema::table('call_logs', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_keys($sm->listTableIndexes(Schema::getConnection()->getTablePrefix() . 'call_logs'));
                if (!in_array('idx_call_logs_phone_created_at', $indexes)) {
                    $table->index(['phonenumber', 'created_at'], 'idx_call_logs_phone_created_at');
                }
            });
        }

        if (Schema::hasTable('dialer_logs')) {
            Schema::table('dialer_logs', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_keys($sm->listTableIndexes(Schema::getConnection()->getTablePrefix() . 'dialer_logs'));
                if (!in_array('idx_dialer_logs_phone_created_at', $indexes)) {
                    $table->index(['phone_number', 'created_at'], 'idx_dialer_logs_phone_created_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_call_logs');
        Schema::dropIfExists('daily_phone_call_attempts');
    }
};
