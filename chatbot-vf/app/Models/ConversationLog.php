<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationLog extends Model
{
    use HasFactory;
    protected $table = 'conversation_logs';
    protected $fillable = [
        'conversation_id',
        'message',
        'type_user',
        'node_id'
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }
}
