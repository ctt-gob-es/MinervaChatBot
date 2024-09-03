<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Events\EventConversation;

class Conversation extends Model
{
    use HasFactory;
    protected $table = 'conversations';

    protected $fillable = [
        'conversation_status_id',
        'finished',
        'agent',
        'chatbot_log_id',
        'language',
        'token'
    ];

    /**
     * Cierra la conversación por inactividad.
     */
    public function closeConversationInactivity()
    {
        $this->conversation_status_id = 3;
        $this->finished = 1;
        $this->save();

        $data = [
            'conversation_id' => $this->id,
            'inactivity' => true
        ];

        EventConversation::dispatch($this->id, $data);
    }

    public function closeChatbotRasaConversation()
    {
        $chatbotsRasa = ChatbotRasa::where('user', $this->id)->get();

        foreach ($chatbotsRasa as $chatbotRasa) {
            $chatbotRasa->status = 0;
            $chatbotRasa->save();
        }
    }

        /**
     * Cierra la conversación por abandono.
     */
    public function closeAbandonment()
    {
        $this->conversation_status_id = 4;
        $this->finished = 1;
        $this->save();
    }

    /**
         * Obtiene todas las conversaciones abiertas.
     */
    public static function getOpenConversations()
    {
        return self::where('conversation_status_id', 1)
                    ->where('finished', 0)
                    ->get();
    }

    public function chatbotLog()
    {
        return $this->belongsTo(ChatbotLog::class);
    }

    public function conversationLogs()
    {
        return $this->hasMany(ConversationLog::class);
    }

    public function node()
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

}
