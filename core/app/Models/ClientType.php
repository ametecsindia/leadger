<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClientType extends Model
{
    use HasFactory;
    protected $table = 'client_types';
    protected $fillable = [
    'name', 'status','description','created_by',
    ];

    public $timestamps = true;
}
