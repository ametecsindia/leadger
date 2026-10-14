<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IncomingVmnLogs extends Model
{
    use HasFactory;
    protected $table="incoming_vmn_logs";
     protected $fillable = ['call_id','dni','a_party_no','a_dial_status','cli','recording_url','duration','response','file_path','created_at','updated_at'];
     public $timestamps = true;
}
