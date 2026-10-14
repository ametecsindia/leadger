<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPhoneCallAttempt extends Model
{
    protected $table = 'daily_phone_call_attempts';

    protected $fillable = [
        'phone_normalized',
        'call_date',
        'attempt_count',
    ];

    protected $casts = [
        'call_date' => 'date',
        'attempt_count' => 'integer',
    ];
}
