<?php

use Illuminate\Database\Migrations\Migration;

/**
 * First version showed the companies list in Leadger_app2 as a VIEW. Replaced by a real table kept in step with the
 * master list: 2026_10_24_120000_companies_list_table_in_app_database (which also removes the view if it was made).
 */
return new class extends Migration
{
    public function up(): void
    {
    }

    public function down(): void
    {
    }
};
