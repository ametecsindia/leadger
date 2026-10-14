<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    use HasFactory;

    protected $table = 'recordings';

    protected $fillable = [
        'provider_call_id',
        'call_id',
        'dialer_log_id',
        'call_log_id',
        'source_code',
        'source_type',
        'user_id',
        'agent_name',
        'lead_id',
        'customer_name',
        'phone_number',
        'campaign_id',
        'campaign_name',
        'call_date',
        'duration',
        'recording_url',
        'file_path',
        'file_size',
        'download_status',
        'download_attempts',
        'call_status',
        'raw_payload',
    ];

    protected $casts = [
        'call_date' => 'datetime',
        'duration' => 'integer',
        'download_attempts' => 'integer',
        'file_size' => 'integer',
        'source_code' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dialerLog()
    {
        return $this->belongsTo(DialerLog::class, 'dialer_log_id');
    }

    public function callLog()
    {
        return $this->belongsTo(CallLog::class, 'call_log_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Formatted duration in mm:ss format.
     */
    public function getFormattedDurationAttribute(): string
    {
        $sec = (int)$this->duration;
        $min = floor($sec / 60);
        $rem = $sec % 60;
        return sprintf('%02d:%02d', $min, $rem);
    }

    /**
     * Check if audio file exists on disk.
     */
    public function isDownloaded(): bool
    {
        return !empty($this->file_path) && file_exists($this->file_path);
    }
}
