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
    public function up(): void
    {
        // Table: smart31_vmn_numbers
        Schema::create('smart31_vmn_numbers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('vmn', 20)->collation('utf8mb4_unicode_ci');
            $table->date('usage_date')->nullable();
            $table->tinyInteger('webrtc')->default(0);
            $table->tinyInteger('c2c')->default(0);
            $table->tinyInteger('obd')->default(0);
            $table->tinyInteger('verified')->default(0);
            $table->unsignedInteger('group_id');
            $table->string('type', 50)->nullable()->collation('utf8mb4_unicode_ci');
            $table->timestamps();
            $table->string('group_name', 50)->collation('utf8mb4_unicode_ci');
            $table->string('assigned_groups', 100)->nullable()->collation('utf8mb4_unicode_ci');
            $table->tinyInteger('is_changed')->default(0);

            $table->index('vmn', 'smart31_vmn_numbers_vmn_index');
            $table->index('group_id', 'smart31_vmn_numbers_group_id_index');
        });

        // Table: smart31_history
        Schema::create('smart31_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('vmn', 20)->collation('utf8mb4_unicode_ci');
            $table->date('usage_date')->nullable();
            $table->tinyInteger('webrtc')->default(0);
            $table->tinyInteger('c2c')->default(0);
            $table->tinyInteger('obd')->default(0);
            $table->tinyInteger('verified')->default(0);
            $table->unsignedInteger('group_id');
            $table->string('type', 50)->nullable()->collation('utf8mb4_unicode_ci');
            $table->timestamps();
            $table->string('group_name', 50)->collation('utf8mb4_unicode_ci');
            $table->string('assigned_groups', 100)->nullable()->collation('utf8mb4_unicode_ci');
            $table->tinyInteger('is_changed')->default(0);

            $table->index('vmn', 'smart31_vmn_numbers_vmn_index');
            $table->index('group_id', 'smart31_vmn_numbers_group_id_index');
        });

        // Table: leadger_obd_did
        Schema::create('obd_did_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('virtual_number', 15)->collation('utf8mb4_unicode_ci');
            $table->tinyInteger('obd');
            $table->tinyInteger('click2call');
            $table->tinyInteger('tc_verified');
            $table->integer('created_by');
            $table->timestamps();
            $table->string('team_id', 200)->nullable()->collation('utf8mb4_unicode_ci');
            $table->binary('expiry');
            $table->bigInteger('voucher_id');
            $table->tinyInteger('webrtc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
};
