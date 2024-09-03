<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    use HasFactory;

    protected $table = 'nodes';

    protected $fillable = [
        'node',
        'name',
        'class',
        'html',
        'typenode',
        'chatbot_log_id',
        'deleted',
        'end',
    ];

    public function chatbotLog()
    {
        return $this->belongsTo(ChatbotLog::class, 'chatbot_log_id');
    }

    public function nodeLanguages()
    {
        return $this->hasMany(NodeLanguage::class);
    }

    public function nodeIntentions()
    {
        return $this->hasMany(NodeIntention::class);
    }

}
