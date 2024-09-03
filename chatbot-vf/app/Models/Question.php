<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $table = 'question';

    protected $fillable = [
        'intentions_id',
    ];

    public function intentions()
    {
        return $this->belongsTo(Intentions::class);
    }

    public function questionLanguages()
    {
        return $this->hasMany(QuestionLanguage::class);
    }
}
