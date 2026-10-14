<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::Statement("insert into leadger_statuses(name,seq_order,status,created_by,lead_pool,created_at,updated_at)
values('DND', 100, 1, 1, 0,current_time,current_time)");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::Statement("delete from leadger_statuses where name = 'DND'");
    }
};
