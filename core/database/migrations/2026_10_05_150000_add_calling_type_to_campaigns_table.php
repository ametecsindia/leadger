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
        if (!Schema::hasColumn('campaigns', 'calling_type')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('calling_type', 50)->nullable()->after('status');
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
        if (Schema::hasColumn('campaigns', 'calling_type')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('calling_type');
            });
        }
    }
};
