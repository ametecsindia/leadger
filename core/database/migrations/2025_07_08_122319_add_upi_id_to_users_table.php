<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns used by the user/payout screens that were never created by a migration
     * (they were added by hand on older databases), so add whichever are missing.
     */
    private $columns = [
        'account_number' => 'string',
        'ifsc_code' => 'string',
        'branch_name' => 'string',
        'upi_id' => 'string',
        'languages_known' => 'string',
        'role_id' => 'unsignedBigInteger',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ($this->columns as $column => $type) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->{$type}($column)->nullable();
                }
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('upi_id');
        });
    }
};
