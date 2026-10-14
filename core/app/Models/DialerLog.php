<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DialerLog extends Model
{
    use HasFactory;

    protected $table = 'dialer_logs';
    protected $guarded = [];

    /**
     * A manual-dial call is never a campaign call: whatever campaign is selected on screen, or the
     * campaign of a lead whose number was dialled, must not be attached. (Campaign calls are stored in
     * call_logs.) The matched lead_id is kept so the call still appears in that lead's history.
     */
    protected static function booted()
    {
        static::saving(function (DialerLog $log) {
            if (($log->call_source ?: 'manual') === 'manual') {
                $log->campaign_id = null;
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function recording()
    {
        return $this->hasOne(Recording::class, 'dialer_log_id');
    }
}
