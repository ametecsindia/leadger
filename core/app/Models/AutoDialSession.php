<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One Auto Dialer session of an agent on the Calling Workspace (Campaign Queue).
 * At most one session per agent is running or paused at a time (enforced by AutoDialerController).
 */
class AutoDialSession extends Model
{
    public const ACTIVE = ['running', 'paused'];

    protected $table = 'auto_dial_sessions';

    protected $guarded = ['id'];

    protected $casts = [
        'dialed_lead_ids' => 'array',
        'skipped' => 'array',
        'pending_outcome' => 'boolean',
        'last_heartbeat_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE, true);
    }

    public function end(string $reason): void
    {
        $this->status = 'ended';
        $this->end_reason = $reason;
        $this->ended_at = now();
        $this->pending_outcome = false;
        $this->save();
    }
}
