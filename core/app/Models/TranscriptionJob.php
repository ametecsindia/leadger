<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TranscriptionJob extends Model
{
    protected $table = 'transcription_jobs';

    public const STATUS_PENDING_CONFIRMATION = 'PENDING_CONFIRMATION';
    public const STATUS_QUEUED = 'QUEUED';
    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const BILLING_PENDING = 'PENDING';
    public const BILLING_BILLED = 'BILLED';
    public const BILLING_NOT_BILLED = 'NOT_BILLED';

    protected $fillable = [
        'batch_id',
        'client_id',
        'requested_by',
        'recording_id',
        'call_id',
        'source_code',
        'customer_name',
        'agent_name',
        'phonenumber',
        'recording_file',
        'duration_seconds',
        'actual_duration_seconds',
        'billable_minutes',
        'actual_billable_minutes',
        'provider',
        'provider_reference',
        'unit_rate',
        'estimated_cost',
        'actual_cost',
        'currency',
        'status',
        'billing_status',
        'error_message',
        'completed_at',
        'billed_at',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'actual_duration_seconds' => 'integer',
        'billable_minutes' => 'decimal:2',
        'actual_billable_minutes' => 'decimal:2',
        'unit_rate' => 'decimal:4',
        'estimated_cost' => 'decimal:4',
        'actual_cost' => 'decimal:4',
        'completed_at' => 'datetime',
        'billed_at' => 'datetime',
    ];

    /** A PROCESSING job beats every ~20 s (updated_at); this long without a beat means its worker stopped. */
    public const STALL_SECONDS = 300;

    /** Processing, but its queue worker stopped (restart, crash, out of memory): it will not finish by itself. */
    public function isStalled(): bool
    {
        return $this->status === self::STATUS_PROCESSING && $this->updated_at
            && $this->updated_at->lt(now()->subSeconds(self::STALL_SECONDS));
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function callAiAnalysis()
    {
        return $this->hasOne(CallAiAnalysis::class, 'recording_id', 'recording_id');
    }
}
