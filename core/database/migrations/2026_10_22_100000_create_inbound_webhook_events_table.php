<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inbound Calls: every webhook request as it arrived (even when it is refused), and pulled call logs that could not be
 * saved, so no provider event is lost and a failed / ignored one can be processed again (inbound:backfill).
 * Kept 30 days (pruned by inbound-calls:pull). Headers are stored without secrets.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inbound_webhook_events')) {
            return;
        }
        Schema::create('inbound_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->dateTime('received_at')->index();
            $table->string('source', 10)->default('webhook');   // webhook | pull
            $table->string('http_method', 10)->nullable();
            $table->string('source_ip', 45)->nullable();
            $table->json('headers')->nullable();
            $table->longText('raw_body')->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->string('processing_status', 20)->default('received')->index();   // received | processed | ignored | failed
            $table->string('failure_reason', 255)->nullable();
            $table->string('call_id', 100)->nullable()->index();
            $table->unsignedBigInteger('inbound_call_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_webhook_events');
    }
};
