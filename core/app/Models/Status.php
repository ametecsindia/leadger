<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Status extends Model
{
    use HasFactory;

    public function substatuses()
    {
        return $this->hasMany(Substatus::class, 'status_id')->orderBy('seq_order', 'asc')->orderBy('id', 'asc');
    }
}
