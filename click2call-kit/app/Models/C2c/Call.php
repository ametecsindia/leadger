<?php

namespace App\Models\C2c;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** One Click2Call call. LeadGER: dialer_logs (manual) / call_logs (campaign). */
class Call extends Model
{
    protected $table = 'c2c_calls';

    public const FINAL_STATES = ['completed', 'busy', 'no-answer', 'failed'];

    protected $fillable = [
        'user_id', 'source', 'lead_id', 'campaign_id', 'customer_number', 'agent_number', 'vmn', 'ref_id', 'provider_call_id',
        'call_state', 'started_at', 'answered_at', 'ended_at', 'talk_seconds', 'cdr', 'error', 'recording_url', 'recording_path',
        'recording_status', 'recording_attempts', 'outcome', 'notes', 'follow_up_at',
    ];

    protected $casts = [
        'cdr' => 'array', 'started_at' => 'datetime', 'answered_at' => 'datetime', 'ended_at' => 'datetime',
        'follow_up_at' => 'datetime', 'talk_seconds' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isFinal(): bool
    {
        return in_array($this->call_state, self::FINAL_STATES, true);
    }
}
