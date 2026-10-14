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
        Schema::create('imap_emails', function (Blueprint $table) {
            $table->id();
            $table->integer('imap_setting_id');
            $table->string('from_email',200);
            $table->text('subject');
            $table->text('body');
            $table->timestamp('email_date')->nullable();
            $table->tinyInteger('mail_parsed')->default('0');
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
        Schema::dropIfExists('imap_emails');
    }
};
