<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptLanguage extends Model
{
    use HasFactory;
    protected $table = 'concept_languages';
    protected $fillable = [
        'question',
        'language',
        'concept_id',
    ];
    
}
