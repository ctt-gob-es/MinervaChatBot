<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    use HasFactory;

    protected $table = 'chatbot_logs';

    protected $fillable = ['chatbot_id', 'user_id', 'flow'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class);
    }

}
