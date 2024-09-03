<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answers extends Model
{
    use HasFactory;

    // DEFINE TYPES
    const MULTIPLE = 0;
    const CORRECT = 1;
    const INCORRECT = 2;

    protected $table = 'answers';

    protected $fillable = [
        'type',
        'intentions_id',
    ];

    public function intentions()
    {
        return $this->belongsTo(Intentions::class);
    }

    public function answersLanguage()
    {
        return $this->hasMany(AnswersLanguage::class);
    }
}
