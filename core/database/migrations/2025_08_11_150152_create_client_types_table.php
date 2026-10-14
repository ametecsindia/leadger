<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_types', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('name'); // Client type name
            $table->text('description')->nullable(); // Description
            $table->boolean('status')->default(1); // 1 = active, 0 = inactive
            $table->unsignedBigInteger('created_by')->nullable(); // User who created
            $table->timestamps();

            // If you want to reference `users` table for created_by
            // $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_types');
    }
};

