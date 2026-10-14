<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;

/**
 * This company's Sarvam AI key and transcription settings (one row; each company has its own database).
 * The key is encrypted at rest (encrypted cast) and hidden from arrays / JSON; only the last 4 characters and the
 * status are ever shown.
 */
class CompanySarvamSetting extends Model
{
    public const STATUS_NOT_CONFIGURED = 'not_configured';
    public const STATUS_CONNECTED = 'connected';
    public const STATUS_INVALID = 'invalid';

    /** Sarvam speech-to-text language codes (docs.sarvam.ai, language_code); "unknown" = auto detect. */
    public const LANGUAGES = [
        'unknown' => 'Auto detect', 'en-IN' => 'English', 'hi-IN' => 'Hindi', 'te-IN' => 'Telugu', 'ta-IN' => 'Tamil',
        'kn-IN' => 'Kannada', 'ml-IN' => 'Malayalam', 'mr-IN' => 'Marathi', 'bn-IN' => 'Bengali', 'gu-IN' => 'Gujarati',
        'pa-IN' => 'Punjabi', 'od-IN' => 'Odia',
    ];

    public const SCOPES = ['connected' => 'Connected calls only', 'all' => 'All recordings', 'none' => 'None (manual only)'];

    protected $table = 'company_sarvam_settings';

    protected $fillable = [
        'company_id', 'sarvam_api_key', 'sarvam_key_last4', 'sarvam_key_status', 'sarvam_key_verified_at',
        'transcription_language', 'transcription_scope', 'auto_transcribe', 'updated_by',
    ];

    protected $hidden = ['sarvam_api_key'];

    protected $casts = [
        'sarvam_api_key' => 'encrypted',
        'auto_transcribe' => 'boolean',
        'sarvam_key_verified_at' => 'datetime',
    ];

    /** The company's settings row (created on first use). */
    public static function current(): self
    {
        return static::firstOrCreate(['company_id' => 1], [
            'sarvam_key_status' => self::STATUS_NOT_CONFIGURED,
            'transcription_language' => 'unknown',
            'transcription_scope' => 'connected',
            'auto_transcribe' => false,
        ]);
    }

    /** The decrypted key, or null (none saved / cannot be decrypted). Never log or return this. */
    public function apiKey(): ?string
    {
        try {
            $key = $this->sarvam_api_key;
        } catch (DecryptException $e) {
            return null;
        }
        return is_string($key) && $key !== '' ? $key : null;
    }

    public function hasKey(): bool
    {
        return $this->sarvam_key_last4 !== null && $this->sarvam_key_last4 !== '';
    }

    /** A key that may be used: saved and not marked invalid. */
    public function usable(): bool
    {
        return $this->hasKey() && $this->sarvam_key_status === self::STATUS_CONNECTED && $this->apiKey() !== null;
    }

    public function maskedKey(): ?string
    {
        return $this->hasKey() ? str_repeat('•', 16) . $this->sarvam_key_last4 : null;
    }
}
