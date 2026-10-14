<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-company Sarvam AI key and transcription settings (each company has its own database, so this one-row table is
 * the company's own), plus an audit trail of key changes (never the key itself).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('company_sarvam_settings')) {
            Schema::create('company_sarvam_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->unique();
                $table->text('sarvam_api_key')->nullable();                     // encrypted (Laravel Crypt), never plain text
                $table->string('sarvam_key_last4', 4)->nullable();
                $table->string('sarvam_key_status', 20)->default('not_configured');   // not_configured | connected | invalid
                $table->timestamp('sarvam_key_verified_at')->nullable();
                $table->string('transcription_language', 10)->default('unknown');    // Sarvam language_code; unknown = auto detect
                $table->string('transcription_scope', 10)->default('connected');     // connected | all | none
                $table->boolean('auto_transcribe')->default(false);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sarvam_key_audit_logs')) {
            Schema::create('sarvam_key_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 191)->nullable();
                $table->string('action', 40);            // added | replaced | removed | tested | settings_changed | invalidated
                $table->string('key_last4', 4)->nullable();
                $table->string('detail', 255)->nullable();
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sarvam_key_audit_logs');
        Schema::dropIfExists('company_sarvam_settings');
    }
};
