<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead Status setting "Only after a connected call": such a status (e.g. Interested, Converted) cannot be saved for a
 * call that did not connect. Off for every status at first, so nothing changes until an admin switches it on.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('statuses', 'needs_connected_call')) {
            Schema::table('statuses', function (Blueprint $t) {
                $t->boolean('needs_connected_call')->default(false)->after('lead_pool');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('statuses', 'needs_connected_call')) {
            Schema::table('statuses', fn (Blueprint $t) => $t->dropColumn('needs_connected_call'));
        }
    }
};
