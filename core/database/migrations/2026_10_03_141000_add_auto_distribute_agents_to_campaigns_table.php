<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('campaigns', 'auto_distribute_agents')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->text('auto_distribute_agents')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('campaigns', 'auto_distribute_agents')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('auto_distribute_agents');
            });
        }
    }
};
