<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsageLog extends Model
{
    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'company_id',
        'call_id',
        'recording_id',
        'provider',
        'service',
        'audio_duration_seconds',
        'usage_amount',
        'currency',
        'status'
    ];

    public function callLog()
    {
        return $this->belongsTo(CallLog::class, 'call_id');
    }
}
