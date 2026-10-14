<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Admin color theme per user: original (default) | slate | green | dark. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'theme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('theme', 20)->default('original');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'theme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('theme');
            });
        }
    }
};
