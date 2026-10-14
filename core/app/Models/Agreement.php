<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    use HasFactory;
    protected $table = 'agreements';
    protected $fillable = [
        'emp_id',
        'advertiser_name',
        'calls_per_week',
        'calls_per_month',
        'preferred_calling_hours',
        'script_file',
        'support_person_name',
        'contact_details',
        'start_date',
        'end_date',
        'uploaded_file',
        'pdf_path',
        'agree_terms',
    ];
}


