<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One inbound call to a company VMN (App\Services\InboundCallService keeps it up to date by call_id). */
class InboundCall extends Model
{
    protected $table = 'inbound_calls';

    protected $fillable = [
        'call_id', 'customer_mobile', 'customer_mobile_normalized', 'vmn', 'start_time', 'end_time',
        'duration_seconds', 'agent_number', 'agent_user_id', 'lead_id', 'status', 'raw_payload',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_seconds' => 'integer',
        'raw_payload' => 'array',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
}
