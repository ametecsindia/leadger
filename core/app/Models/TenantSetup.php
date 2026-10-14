<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class TenantSetup extends Model
{
    
    protected $table="tenants";
    protected $fillable = [
        'subdomain',
        'db_host',
        'db_name',
        'db_user',
        'db_password',
    ];
}
