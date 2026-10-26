<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One saved transcript option result of a recording (see App\Services\TranscriptOptionService). */
class TranscriptResult extends Model
{
    public const OPTIONS = ['summary' => 'Summary', 'agent_customer' => 'Agent and Customer', 'description' => 'Description'];
    public const TITLES = ['summary' => 'Call Summary', 'agent_customer' => 'Agent & Customer Conversation Analysis',
        'description' => 'Detailed Call Conversation'];

    protected $fillable = ['company_id', 'source_code', 'log_id', 'option', 'status', 'result', 'model', 'usage_tokens',
        'error_message', 'requested_by', 'transcription_job_id', 'completed_at'];

    protected $casts = ['result' => 'array', 'completed_at' => 'datetime'];
}
