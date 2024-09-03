<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionLanguage extends Model
{
    use HasFactory;

    protected $table = 'question_languages';

    protected $fillable = [
        'question',
        'language',
        'question_id',
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
