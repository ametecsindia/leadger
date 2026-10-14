<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsHistory extends Model
{
    use HasFactory;
    
    protected $table = 'sms_history';
}
