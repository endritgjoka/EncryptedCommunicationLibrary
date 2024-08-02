<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'encryption_key',
        'encrypted_content',
        'iv',
        'type',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_messages');
    }
}
