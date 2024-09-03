<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationIntention extends Model
{
    use HasFactory;
    protected $table = 'conversation_intentions';
    protected $fillable = [
        'conversation_id',
        'intention_id',
        'question',
        'answer',
        'manual_rating',
        'training_status_id',
        'type'
    ];

    public function intention()
    {
        return $this->belongsTo(Intentions::class, 'intention_id');
    }
}
