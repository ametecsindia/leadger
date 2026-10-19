<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound Calls report: one row per provider call (call_id), filled by the provider webhook and the 5-minute pull.
 * Each company has its own database (App\Support\Tenancy), so the table needs no company column.
 * Times are stored in the app timezone (Asia/Kolkata), as every other call table.
 * Also adds the "Inbound Calls" page permission, on for every role (all roles open the Call Report today).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('inbound_calls')) {
            Schema::create('inbound_calls', function (Blueprint $table) {
                $table->id();
                $table->string('call_id', 100)->unique();
                $table->string('customer_mobile', 30)->nullable();
                $table->string('customer_mobile_normalized', 20)->nullable()->index();
                $table->string('vmn', 30)->nullable()->index();
                $table->dateTime('start_time')->nullable();
                $table->dateTime('end_time')->nullable();
                $table->unsignedInteger('duration_seconds')->nullable();
                $table->string('agent_number', 30)->nullable();
                $table->foreignId('agent_user_id')->nullable()->index()->constrained('users')->nullOnDelete();
                $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
                $table->string('status', 20)->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();
                $table->index('start_time');
            });
        }

        if (!Schema::hasColumn('permissions', 'inbound_calls_status')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->boolean('inbound_calls_status')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_calls');
        if (Schema::hasColumn('permissions', 'inbound_calls_status')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->dropColumn('inbound_calls_status');
            });
        }
    }
};
