<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotRasa extends Model
{
    use HasFactory;

    protected $table = 'chatbot_rasa';

    protected $fillable = [
        'chatbot_id',
        'text',
        'intention',
        'slots',
        'user',
        'form',
        'question_citizen',
        'status',
    ];

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }
}
