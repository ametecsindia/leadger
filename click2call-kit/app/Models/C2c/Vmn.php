<?php

namespace App\Models\C2c;

use Illuminate\Database\Eloquent\Model;

/** A virtual number (VMN / DID) from an Add Voucher. LeadGER: obd_did. */
class Vmn extends Model
{
    protected $table = 'c2c_vmns';

    protected $fillable = ['virtual_number', 'click2call', 'webrtc', 'obd', 'tc_verified', 'team_ids', 'expires_at', 'voucher_id', 'created_by'];

    protected $casts = [
        'click2call' => 'boolean', 'webrtc' => 'boolean', 'obd' => 'boolean', 'tc_verified' => 'boolean',
        'team_ids' => 'array', 'expires_at' => 'date',
    ];

    /** Usable today: no expiry, or the expiry day has not passed. */
    public function isAvailable(): bool
    {
        return !$this->expires_at || $this->expires_at->endOfDay()->gte(now());
    }
}
