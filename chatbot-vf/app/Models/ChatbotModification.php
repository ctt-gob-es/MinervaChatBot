<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotModification extends Model
{
    use HasFactory;

    protected $table = 'chatbot_modifications';

    const CHAT_CREATED = 'Chat creado';
    const CHAT_ENABLED = 'Chat habilitado';
    const CHAT_DISABLED = 'Chat deshabilitado';
    const CHAT_DELETED = 'Chat eliminado';
    const FLOW_UPDATED = 'Flujo de conversación modificado';

    protected $fillable = ['action', 'chatbot_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }
}
