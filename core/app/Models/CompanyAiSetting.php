<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyAiSetting extends Model
{
    protected $table = 'company_ai_settings';

    protected $fillable = [
        'company_id',
        'ai_enabled',
        'provider',
        'auto_analysis_enabled',
        'transcript_enabled',
        'summary_enabled',
        'interest_enabled',
        'sentiment_enabled',
        'intent_enabled',
        'key_points_enabled',
        'next_action_enabled',
        'auto_disposition_enabled',
        'billing_mode',
        'price_per_unit',
        'pricing_unit',
        'currency',
        'client_billing_enabled'
    ];

    protected $casts = [
        'ai_enabled' => 'boolean',
        'auto_analysis_enabled' => 'boolean',
        'transcript_enabled' => 'boolean',
        'summary_enabled' => 'boolean',
        'interest_enabled' => 'boolean',
        'sentiment_enabled' => 'boolean',
        'intent_enabled' => 'boolean',
        'key_points_enabled' => 'boolean',
        'next_action_enabled' => 'boolean',
        'auto_disposition_enabled' => 'boolean',
        'client_billing_enabled' => 'boolean',
        'price_per_unit' => 'decimal:4',
    ];

    /**
     * The one AI settings row for this installation (each tenant has its own database).
     * Previously rows were keyed by the logged-in user's id, so a toggle saved by one
     * admin was invisible to every other user. Everything now reads/writes company_id = 1.
     */
    public static function company(): self
    {
        return static::firstOrCreate(['company_id' => 1], [
            'ai_enabled' => false,
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
        ]);
    }

    /**
     * True when new recordings are transcribed automatically. Decided by the company's Transcription settings
     * (Configuration -> Profile -> License Info: "Transcribe new recordings automatically", scope, valid key).
     */
    public function autoTranscriptEnabled(): bool
    {
        return \App\Services\SarvamKeyService::autoTranscribeOn();
    }
}
