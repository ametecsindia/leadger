<?php

namespace App\Models\C2c;

use Illuminate\Database\Eloquent\Model;

/** A voucher that was added (history). LeadGER: obd_credits_history. */
class Voucher extends Model
{
    protected $table = 'c2c_vouchers';

    protected $fillable = ['voucher_code', 'numbers', 'expires_at', 'created_by'];

    protected $casts = ['expires_at' => 'date'];
}
