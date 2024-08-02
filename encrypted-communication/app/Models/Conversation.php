<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;
    protected $primaryKey = 'id';
    protected $fillable = [
        'id', 'sender_id', 'recipient_id', 'type','unread_messages'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
    public function messages()
    {
        return $this->belongsToMany(Message::class, 'conversation_messages');
    }

    public function lastMessage()
    {
        return $this->hasOne(ConversationMessage::class)
            ->latestOfMany()
            ->with('message.user');
    }

}
