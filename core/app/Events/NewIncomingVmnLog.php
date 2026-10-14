<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewIncomingVmnLog implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */


    public $log;
    protected $userId;

    public function __construct($data)
    {
        $this->log = $data;
        $user = \App\Models\User::where('click_agent_number', $data->a_party_no)->first();

        $this->userId = $user?->id;

    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        if ($this->userId) {
            return new PrivateChannel('incoming-vmn.' . $this->userId);
        }

        // Default fallback: no channel
        return [];
    }
        public function broadcastWith()
    {
        return [
            'title' => 'New VMN Call Received',
            'body' => "CLI: {$this->log->cli}, Duration: {$this->log->duration}s",
        ];
    }
        public function broadcastAs()
    {
        return 'new-incoming-vmn';
    }
}
