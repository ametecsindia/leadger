<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallAiAnalysis extends Model
{
    protected $table = 'call_ai_analyses';

    protected $fillable = [
        'company_id',
        'call_id',
        'recording_id',
        'transcript',
        'transcript_original',
        'transcript_english',
        'conversation',
        'speaker_roles',
        'source_language',
        'analysis_source',
        'summary',
        'interest_level',
        'sentiment',
        'intent',
        'key_points',
        'next_action',
        'follow_up_date',
        'auto_disposition',
        'status',
        'error_message',
        'sarvam_request_id',
        'audio_duration_seconds',
        'ai_cost',
        'emotion_analysis',
        'quality_flags'
    ];

    protected $casts = [
        'conversation' => 'array',
        'speaker_roles' => 'array',
    ];

    public function callLog()
    {
        return $this->belongsTo(CallLog::class, 'call_id');
    }

    /**
     * Keys used to link an analysis to a Recordings-page row.
     * 0 = call_logs (call_id = log id), 2 = dialer_logs ('dialer_{id}'), 3 = standalone recordings ('rec_{id}').
     */
    public static function keysFor(int $sourceCode, int $logId): array
    {
        if ($sourceCode == 2) {
            return ['call_id' => 9000000 + $logId, 'recording_id' => 'dialer_' . $logId];
        }
        if ($sourceCode == 3) {
            return ['call_id' => 8000000 + $logId, 'recording_id' => 'rec_' . $logId];
        }
        return ['call_id' => $logId, 'recording_id' => 'call_' . $logId];
    }

    public static function findFor(int $sourceCode, int $logId): ?self
    {
        if ($sourceCode == 0) {
            return static::where('call_id', $logId)->first();
        }
        return static::where('recording_id', static::keysFor($sourceCode, $logId)['recording_id'])->first();
    }

    public static function firstOrNewFor(int $sourceCode, int $logId): self
    {
        $analysis = static::findFor($sourceCode, $logId) ?: new static();
        $keys = static::keysFor($sourceCode, $logId);
        $analysis->call_id = $keys['call_id'];
        $analysis->recording_id = $keys['recording_id'];
        return $analysis;
    }
}
