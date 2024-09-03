<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeTransition extends Model
{
    use HasFactory;
    protected $table = 'nodes_transitions';

    protected $fillable = [
        'origin',
        'transition',
        'destination',
        'deleted',
        'chatbot_log_id',
    ];

    public function chatbotLog()
    {
        return $this->belongsTo(ChatbotLog::class, 'chatbot_log_id');
    }
}
