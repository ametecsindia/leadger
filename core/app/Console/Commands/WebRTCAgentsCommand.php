<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SmartPingAgent;
use Illuminate\Console\Command;

/**
 * WebRTC agents of the users (a WebRTC call needs one).
 *   php artisan webrtc:agents                      list who has an agent ID
 *   php artisan webrtc:agents --create             create a SmartPing agent for every user without one
 *   php artisan webrtc:agents --create --user=1    only these users
 *   php artisan webrtc:agents --assign=1 --agent=1001   give user 1 an agent ID the provider issued (asks for its SIP password)
 */
class WebRTCAgentsCommand extends Command
{
    protected $signature = 'webrtc:agents {--create : create SmartPing agents for users without one}
        {--user=* : only these user ids} {--assign= : user id to give an existing agent ID} {--agent= : the agent ID (with --assign)}';
    protected $description = 'List, create or assign the WebRTC (SmartPing) agents of users';

    public function handle(): int
    {
        if ($this->option('assign')) {
            return $this->assign();
        }

        $users = User::orderBy('id')->when($this->option('user'), fn ($q, $ids) => $q->whereIn('id', $ids))->get();
        if ($this->option('create')) {
            $this->line('SmartPing company: ' . (config('services.smartping.company_id') ?: '(not set: SMARTPING_COMPANY_ID)'));
            $rows = [];
            foreach ($users as $user) {
                if (trim((string)$user->smartping_user_id) !== '') {
                    $rows[] = [$user->id, $user->name, 'already has ' . $user->smartping_user_id];
                    continue;
                }
                $r = SmartPingAgent::create($user);
                $rows[] = [$user->id, $user->name, $r['ok'] ? 'CREATED ' . $r['agent_id'] : 'not created: ' . $r['message']];
            }
            $this->table(['ID', 'User', 'Result'], $rows);
            return self::SUCCESS;
        }

        $this->table(['ID', 'User', 'Mobile', 'WebRTC agent'], $users->map(fn ($u) => [
            $u->id, $u->name, $u->mobile ?: '-', trim((string)$u->smartping_user_id) !== '' ? $u->smartping_user_id : 'NO AGENT ID',
        ])->all());
        return self::SUCCESS;
    }

    private function assign(): int
    {
        $user = User::find((int)$this->option('assign'));
        $agent = trim((string)$this->option('agent'));
        if (!$user || $agent === '') {
            $this->error('Use --assign=<user id> --agent=<agent ID>.');
            return self::INVALID;
        }
        $password = (string)$this->secret('SIP password of agent ' . $agent . ' (leave empty to keep the current one)');
        $user->smartping_user_id = $agent;
        if ($password !== '') {
            $user->smartping_password = $password;
        }
        $user->save();
        $this->info("User {$user->id} ({$user->name}) now uses WebRTC agent {$agent}.");
        return self::SUCCESS;
    }
}
