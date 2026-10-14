<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Create transcription_jobs table
        if (!Schema::hasTable('transcription_jobs')) {
            Schema::create('transcription_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('batch_id', 64)->index();
                $table->unsignedBigInteger('client_id')->default(1)->index();
                $table->unsignedBigInteger('requested_by')->default(1)->index();
                $table->string('recording_id')->index();
                $table->unsignedBigInteger('call_id')->index();
                $table->tinyInteger('source_code')->default(0); // 0=Campaign, 2=Dialer
                $table->string('customer_name')->nullable();
                $table->string('agent_name')->nullable();
                $table->string('phonenumber', 50)->nullable();
                $table->text('recording_file')->nullable();
                $table->integer('duration_seconds')->default(0);
                $table->decimal('billable_minutes', 10, 2)->default(0.00);
                $table->string('provider', 50)->default('Sarvam AI');
                $table->string('provider_reference')->nullable();
                $table->decimal('unit_rate', 10, 4)->default(0.1500);
                $table->decimal('estimated_cost', 10, 4)->default(0.0000);
                $table->decimal('actual_cost', 10, 4)->default(0.0000);
                $table->string('currency', 10)->default('INR');
                $table->string('status', 30)->default('PENDING_CONFIRMATION')->index(); 
                // PENDING_CONFIRMATION, CONFIRMED, QUEUED, PROCESSING, COMPLETED, FAILED, CANCELLED, BILLED
                $table->text('error_message')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('billed_at')->nullable();
                $table->timestamps();

                $table->index(['client_id', 'status']);
                $table->index(['call_id', 'source_code']);
            });
        }

        // 2. Add pricing columns to company_ai_settings if missing
        if (Schema::hasTable('company_ai_settings')) {
            Schema::table('company_ai_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('company_ai_settings', 'price_per_unit')) {
                    $table->decimal('price_per_unit', 10, 4)->default(0.1500)->after('billing_mode');
                }
                if (!Schema::hasColumn('company_ai_settings', 'pricing_unit')) {
                    $table->string('pricing_unit', 20)->default('minute')->after('price_per_unit');
                }
                if (!Schema::hasColumn('company_ai_settings', 'currency')) {
                    $table->string('currency', 10)->default('INR')->after('pricing_unit');
                }
                if (!Schema::hasColumn('company_ai_settings', 'client_billing_enabled')) {
                    $table->boolean('client_billing_enabled')->default(true)->after('currency');
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
        Schema::dropIfExists('transcription_jobs');

        if (Schema::hasTable('company_ai_settings')) {
            Schema::table('company_ai_settings', function (Blueprint $table) {
                if (Schema::hasColumn('company_ai_settings', 'client_billing_enabled')) {
                    $table->dropColumn('client_billing_enabled');
                }
                if (Schema::hasColumn('company_ai_settings', 'currency')) {
                    $table->dropColumn('currency');
                }
                if (Schema::hasColumn('company_ai_settings', 'pricing_unit')) {
                    $table->dropColumn('pricing_unit');
                }
                if (Schema::hasColumn('company_ai_settings', 'price_per_unit')) {
                    $table->dropColumn('price_per_unit');
                }
            });
        }
    }
};
