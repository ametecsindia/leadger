<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Click2CallLog extends Model
{
    use HasFactory;

    protected $table = 'click2call_logs';

    protected $fillable = [
        'uuid',
        'lead_id',
        'reference_id',
        'vmn',
        'user_id',
        'response',
        'duration',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
