<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotPort extends Model
{
    use HasFactory;

    public $table = 'chatbot_port';

    protected $fillable = ['chatbots_id', 'port', 'language'];

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class, 'chatbots_id');
    }
}
