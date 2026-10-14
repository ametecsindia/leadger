<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedCallLog extends Model
{
    protected $table = 'blocked_call_logs';

    protected $fillable = [
        'phone_number',
        'phone_normalized',
        'user_id',
        'agent_name',
        'reason',
        'message',
        'channel',
        'blocked_at',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
