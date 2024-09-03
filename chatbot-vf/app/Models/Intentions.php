<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Intentions extends Model
{
    use HasFactory,SoftDeletes;


    protected $table = 'intentions';

    protected $fillable = ['name', 'information', 'validated', 'creation_method', 'creator', 'chatbot_id', 'subjects_id', 'training'];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subjects_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'creator');
    }
    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    public function answers()
    {
        return $this->hasMany(Answers::class);
    }
    public function concepts()
    {
        return $this->belongsToMany(Concept::class, 'intentions_concepts', 'intention_id');
    }
    public function intentionLanguages()
    {
        return $this->hasMany(IntentionLanguage::class, 'intention_id');
    }
    public function modifications()
    {
        return $this->hasMany(IntentionModification::class, 'intention_id');
    }
    public function conversationIntentions()
    {
        return $this->hasMany(ConversationIntention::class, 'intention_id');
    }
}
