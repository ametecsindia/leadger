
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAiCallIntelligenceTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Company AI Settings Table
        if (!Schema::hasTable('company_ai_settings')) {
            Schema::create('company_ai_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->boolean('ai_enabled')->default(true); // Default enabled for UI demo prototype
                $table->string('provider')->default('Sarvam AI');
                $table->boolean('auto_analysis_enabled')->default(true);
                $table->boolean('transcript_enabled')->default(true);
                $table->boolean('summary_enabled')->default(true);
                $table->boolean('interest_enabled')->default(true);
                $table->boolean('sentiment_enabled')->default(true);
                $table->boolean('intent_enabled')->default(true);
                $table->boolean('key_points_enabled')->default(true);
                $table->boolean('next_action_enabled')->default(true);
                $table->boolean('auto_disposition_enabled')->default(true);
                $table->string('billing_mode')->default('Usage Based');
                $table->timestamps();
            });

            // Insert initial default setting record
            DB::table('company_ai_settings')->insert([
                'company_id' => 1,
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Call AI Analyses Table
        if (!Schema::hasTable('call_ai_analyses')) {
            Schema::create('call_ai_analyses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->unsignedBigInteger('call_id')->index();
                $table->string('recording_id')->nullable()->index();
                $table->longText('transcript')->nullable();
                $table->text('summary')->nullable();
                $table->string('interest_level')->nullable(); // HIGH, MEDIUM, LOW
                $table->string('sentiment')->nullable(); // POSITIVE, NEUTRAL, NEGATIVE
                $table->string('intent')->nullable(); // Product Enquiry, Pricing Request, Follow-up, Not Interested, etc.
                $table->text('key_points')->nullable(); // JSON or bulleted string
                $table->text('next_action')->nullable();
                $table->dateTime('follow_up_date')->nullable();
                $table->string('auto_disposition')->nullable();
                $table->string('status')->default('pending')->index(); // pending, processing, completed, failed
                $table->text('error_message')->nullable();
                $table->string('sarvam_request_id')->nullable();
                $table->integer('audio_duration_seconds')->default(0);
                $table->decimal('ai_cost', 10, 4)->default(0.0000);
                $table->timestamps();
            });
        }

        // 3. AI Usage Logs Table
        if (!Schema::hasTable('ai_usage_logs')) {
            Schema::create('ai_usage_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->unsignedBigInteger('call_id')->nullable()->index();
                $table->string('recording_id')->nullable()->index();
                $table->string('provider')->default('Sarvam AI');
                $table->string('service')->default('Speech-to-Text & Call Intelligence');
                $table->integer('audio_duration_seconds')->default(0);
                $table->decimal('usage_amount', 10, 4)->default(0.0000);
                $table->string('currency')->default('INR');
                $table->string('status')->default('completed'); // completed, failed
                $table->timestamps();
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
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('call_ai_analyses');
        Schema::dropIfExists('company_ai_settings');
    }
}
