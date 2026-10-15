<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns and tables the app uses that were added by hand on older databases and never had a migration,
 * so a freshly migrated database (e.g. a new live server) failed with "Unknown column ...".
 * Everything is skipped when it already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addColumns('call_logs', [
            'uuid' => fn (Blueprint $t) => $t->string('uuid', 100)->nullable(),
            'call_source' => fn (Blueprint $t) => $t->string('call_source', 20)->default('campaign'),
            'call_state' => fn (Blueprint $t) => $t->string('call_state', 50)->nullable()->default('initiated'),
            'start_time' => fn (Blueprint $t) => $t->timestamp('start_time')->nullable(),
            'end_time' => fn (Blueprint $t) => $t->timestamp('end_time')->nullable(),
            'campaign_id' => fn (Blueprint $t) => $t->integer('campaign_id')->nullable(),
            'reference_id' => fn (Blueprint $t) => $t->string('reference_id', 191)->nullable()->index(),
            'substatus_id' => fn (Blueprint $t) => $t->integer('substatus_id')->nullable(),
        ]);

        $this->addColumns('dialer_logs', [
            'substatus_id' => fn (Blueprint $t) => $t->integer('substatus_id')->nullable(),
            'call_source' => fn (Blueprint $t) => $t->string('call_source', 20)->default('manual'),
            'reference_id' => fn (Blueprint $t) => $t->string('reference_id', 191)->nullable()->index(),
        ]);

        $this->addColumns('settings', [
            'caller_login' => fn (Blueprint $t) => $t->time('caller_login')->nullable()->default('10:30:00'),
            'caller_logout' => fn (Blueprint $t) => $t->time('caller_logout')->nullable()->default('17:30:00'),
        ]);

        $this->addColumns('users', [
            'flexidial_status' => fn (Blueprint $t) => $t->tinyInteger('flexidial_status')->default(0),
            'flexidial_channels' => fn (Blueprint $t) => $t->integer('flexidial_channels')->default(0),
            'flexidial_call_rate' => fn (Blueprint $t) => $t->integer('flexidial_call_rate')->default(0),
            'flexidial_parallel_users' => fn (Blueprint $t) => $t->integer('flexidial_parallel_users')->default(1),
            'webrtc_flexidial_status' => fn (Blueprint $t) => $t->tinyInteger('webrtc_flexidial_status')->default(0),
            'webrtc_flexidial_channels' => fn (Blueprint $t) => $t->integer('webrtc_flexidial_channels')->default(0),
            'webrtc_flexidial_call_rate' => fn (Blueprint $t) => $t->integer('webrtc_flexidial_call_rate')->default(0),
            'webrtc_flexidial_parallel_users' => fn (Blueprint $t) => $t->integer('webrtc_flexidial_parallel_users')->default(1),
        ]);

        if (Schema::hasTable('call_logs') && !Schema::hasIndex('call_logs', 'idx_call_logs_campaign_created_at')) {
            Schema::table('call_logs', fn (Blueprint $t) => $t->index(['campaign_id', 'created_at'], 'idx_call_logs_campaign_created_at'));
        }

        $this->createTable('agreements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('emp_id');
            $table->string('advertiser_name');
            $table->integer('calls_per_week')->nullable();
            $table->integer('calls_per_month')->nullable();
            $table->string('preferred_calling_hours')->nullable();
            $table->string('script_file')->nullable();
            $table->string('support_person_name')->nullable();
            $table->string('contact_details')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('uploaded_file')->nullable();
            $table->string('pdf_path')->nullable();
            $table->boolean('agree_terms')->nullable()->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        $this->createTable('click2call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 100)->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('reference_id', 150);
            $table->string('vmn', 20)->nullable();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('response')->nullable();
            $table->integer('duration')->nullable()->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });

        $this->createTable('flexi_dialer_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_code', 100);
            $table->binary('expiry');
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'EXPIRED'])->default('ACTIVE');
            $table->integer('channels');
            $table->integer('user_id');
            $table->timestamps();
            $table->text('channel_type')->nullable();
            $table->string('type')->nullable();
            $table->integer('webrtc_channels')->nullable();
        });

        $this->createTable('sidebar_menus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('folder_name', 100)->nullable();
            $table->string('submenu', 100)->nullable();
            $table->boolean('active')->nullable()->default(1);
        });

        $this->createTable('webrtc_flexidialer', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('lead_id')->index();
            $table->integer('campaign_id')->index();
            $table->string('mobile_number', 20)->index();
            $table->tinyInteger('dial_status')->default(0);
            $table->string('lead_status', 50)->nullable();
            $table->string('c2c_status', 100)->nullable();
            $table->integer('agent_id')->index();
            $table->string('agent_did', 20)->nullable();
            $table->integer('call_log_id')->nullable()->index();
            $table->string('agent_number', 20)->nullable();
            $table->timestamps();
        });

        $this->createTable('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue', 191)->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        // Not reversible: on older databases these columns and tables existed before this migration.
    }

    private function addColumns(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $column => $define) {
            if (!Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $t) => $define($t));
            }
        }
    }

    private function createTable(string $table, Closure $define): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $define);
        }
    }
};
