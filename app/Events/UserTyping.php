<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user_id;
    public $to_user_id;
    public $is_typing;

    public function __construct($from_user_id, $to_user_id, $is_typing)
    {
        $this->user_id = $from_user_id;
        $this->to_user_id = $to_user_id;
        $this->is_typing = $is_typing;
    }

    public function broadcastOn()
    {
        return new PresenceChannel('presence-chat.' . $this->to_user_id);
    }

    public function broadcastAs()
    {
        return 'typing';
    }
}

