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
    Schema::create('payout', function (Blueprint $table) {
                $table->increments('id');
                $table->string('role_name');
                $table->enum('payout_type', ['paybycall', 'paybyduration', 'both']);

                // Call-based ranges
                $table->integer('first_range_min_call')->nullable();
                $table->integer('first_range_max_call')->nullable();
                $table->decimal('first_range_pay_call', 10, 2)->nullable();
                $table->integer('second_range_min_call')->nullable();
                $table->integer('second_range_max_call')->nullable();
                $table->decimal('second_range_pay_call', 10, 2)->nullable();
                $table->integer('third_range_min_call')->nullable();
                $table->integer('third_range_max_call')->nullable();
                $table->decimal('third_range_pay_call', 10, 2)->nullable();
                $table->integer('fourth_range_min_call')->nullable();
                $table->integer('fourth_range_max_call')->nullable();
                $table->decimal('fourth_range_pay_call', 10, 2)->nullable();
                // Duration-based ranges
                $table->integer('first_range_min_duration')->nullable();
                $table->integer('first_range_max_duration')->nullable();
                $table->decimal('first_range_pay_duration', 10, 2)->nullable();
                $table->integer('second_range_min_duration')->nullable();
                $table->integer('second_range_max_duration')->nullable();
                $table->decimal('second_range_pay_duration', 10, 2)->nullable();
                $table->integer('third_range_min_duration')->nullable();
                $table->integer('third_range_max_duration')->nullable();
                $table->decimal('third_range_pay_duration', 10, 2)->nullable();
                $table->integer('fourth_range_min_duration')->nullable();
                $table->integer('fourth_range_max_duration')->nullable();
                $table->decimal('fourth_range_pay_duration', 10, 2)->nullable();
                $table->timestamps();

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
