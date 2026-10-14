<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class, 'team_user', 'team_id', 'user_id')->withTimestamps();
    }
}
