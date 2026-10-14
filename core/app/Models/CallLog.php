<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CallLog extends Model
{
    use HasFactory;

    protected $table = 'call_logs';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function recordingRelation()
    {
        return $this->hasOne(Recording::class, 'call_log_id');
    }
}
