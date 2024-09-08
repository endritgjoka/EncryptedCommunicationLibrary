<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $recipientId;
    public $otherUser;
    public $conversationType;
    public $encryptionKey;


    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, $recipientId, $otherUser, $conversationType, $encryptionKey)
    {
        $this->message = $message;
        $this->recipientId = $recipientId;
        $this->otherUser = $otherUser;
        $this->conversationType = $conversationType;
        $this->encryptionKey = $encryptionKey;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.'.$this->recipientId);
    }


    public function broadcastWith()
    {
        return [
            'other_user_id' => $this->otherUser,
            'message' => $this->message,
            'conversation_type' => $this->conversationType,
            'encryption_key' => $this->encryptionKey
        ];
    }

    public function broadcastAs()
    {
        return 'NewMessage';
    }
}
