<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    // Table name (optional if it follows Laravel's naming convention)
    protected $table = 'payout';
    protected $fillable = [
    'role_name', 'payout_type',

    'first_range_min_call', 'first_range_max_call','first_range_pay_call',
    'second_range_min_call', 'second_range_max_call','second_range_pay_call',
    'third_range_min_call', 'third_range_max_call','third_range_pay_call',
    'fourth_range_min_call', 'fourth_range_max_call','fourth_range_pay_call',

    'first_range_min_duration', 'first_range_max_duration','first_range_pay_duration',
    'second_range_min_duration', 'second_range_max_duration','second_range_pay_duration',
    'third_range_min_duration', 'third_range_max_duration','third_range_pay_duration',
    'fourth_range_min_duration', 'fourth_range_max_duration','fourth_range_pay_duration',
    ];

    public $timestamps = true;

}
