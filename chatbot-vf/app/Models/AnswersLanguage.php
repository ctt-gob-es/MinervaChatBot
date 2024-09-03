<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnswersLanguage extends Model
{
    use HasFactory;

    protected $table = 'answers_languages';

    protected $fillable = [
        'answers',
        'language',
        'answers_id',
    ];

    public function answers()
    {
        return $this->belongsTo(Answers::class);
    }
}
