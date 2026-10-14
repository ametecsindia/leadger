<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateTeamUserPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('team_user')) {
            Schema::create('team_user', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('team_id')->index();
                $table->timestamps();

                $table->unique(['user_id', 'team_id']);
            });

            // Populate existing user-team relationships safely from users.team_id
            try {
                $users = DB::table('users')->select('id', 'team_id')->get();
                $validTeamIds = DB::table('teams')->pluck('id')->toArray();
                $now = now();
                $inserts = [];

                foreach ($users as $user) {
                    if (!empty($user->team_id)) {
                        $tIds = explode(',', (string)$user->team_id);
                        foreach ($tIds as $tId) {
                            $tId = (int)trim($tId);
                            if ($tId > 0 && in_array($tId, $validTeamIds)) {
                                $key = $user->id . '_' . $tId;
                                $inserts[$key] = [
                                    'user_id' => $user->id,
                                    'team_id' => $tId,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        }
                    }
                }

                if (!empty($inserts)) {
                    DB::table('team_user')->insert(array_values($inserts));
                }
            } catch (\Exception $e) {
                // If tables are empty or error occurs, continue safely
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('team_user');
    }
}
