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
        Schema::table('call_ai_analyses', function (Blueprint $table) {
            if (!Schema::hasColumn('call_ai_analyses', 'emotion_analysis')) {
                $table->text('emotion_analysis')->nullable()->after('key_points');
            }
            if (!Schema::hasColumn('call_ai_analyses', 'quality_flags')) {
                $table->text('quality_flags')->nullable()->after('emotion_analysis');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_ai_analyses', function (Blueprint $table) {
            if (Schema::hasColumn('call_ai_analyses', 'quality_flags')) {
                $table->dropColumn('quality_flags');
            }
            if (Schema::hasColumn('call_ai_analyses', 'emotion_analysis')) {
                $table->dropColumn('emotion_analysis');
            }
        });
    }
};
