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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->integer('campaign_id');
            $table->string('name',100)->nullable();
            $table->string('designation',100)->nullable();
            $table->string('position',100)->nullable();
            $table->string('company',100)->nullable();
            $table->string('description',1000)->nullable();
            $table->string('country',30)->nullable();
            $table->string('pin_code',10)->nullable();
            $table->string('city',100)->nullable();
            $table->string('state',100)->nullable();
            $table->string('address',300)->nullable();
            $table->integer('status')->nullable();
            $table->integer('substatus')->nullable();
            $table->string('source',100)->nullable();
            $table->string('email',100)->nullable();
            $table->string('website',100)->nullable();
            $table->string('phonenumber',20)->nullable();
            $table->string('phonenumber_2',20)->nullable();
            $table->integer('lead_ranking')->nullable();
            $table->string('latitude',20)->nullable();
            $table->string('longitude',20)->nullable();
            $table->string('lead_value',20)->nullable();
            $table->string('product_service',200)->nullable();
            $table->string('tags',1000)->nullable();
            $table->integer('assigned_to')->nullable();
            $table->integer('sales_person_id')->nullable();
            $table->integer('last_actioned_by')->nullable();
            $table->timestamp('last_actioned_date')->nullable();
            $table->timestamp('follow_up_on')->nullable();
            $table->integer('reminder')->nullable();
            $table->string('remarks',1000)->nullable();
            $table->tinyInteger('is_public')->default('0');
            $table->integer('created_by')->nullable();
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
        Schema::dropIfExists('leads');
    }
};
