<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexed lookup of a lead by phone number in any format: phonenumber / phonenumber_2 as digits only, without a
 * leading 0 or the 91 country code (same rule as InboundCallService::normalizeMobile). Virtual columns: nothing is
 * stored or written by the app; every INSERT into leads lists its columns, so they are never given a value.
 */
return new class extends Migration
{
    private function expr(string $col): string
    {
        $d = "REGEXP_REPLACE(REGEXP_REPLACE(COALESCE(`$col`, ''), '[^0-9]', ''), '^0+', '')";
        return "(CASE WHEN CHAR_LENGTH($d) = 12 AND LEFT($d, 2) = '91' THEN SUBSTRING($d, 3) ELSE $d END)";
    }

    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'phone_normalized')) {
                $table->string('phone_normalized', 30)->virtualAs($this->expr('phonenumber'))->nullable()->index();
            }
            if (!Schema::hasColumn('leads', 'phone2_normalized')) {
                $table->string('phone2_normalized', 30)->virtualAs($this->expr('phonenumber_2'))->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['phone_normalized']);
            $table->dropIndex(['phone2_normalized']);
            $table->dropColumn(['phone_normalized', 'phone2_normalized']);
        });
    }
};
