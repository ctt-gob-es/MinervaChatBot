<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concept extends Model
{
    use HasFactory;

    protected $table = 'concepts';

    protected $fillable = [
        'name',
        'chatbot_id',
    ];

    // No necesitas definir created_at ni updated_at aquí,
    public function intentions()
    {
        return $this->belongsToMany(Intentions::class, 'intentions_concepts', 'concept_id');
    }
    public function lists()
    {
        return $this->belongsToMany(Lists::class, 'concepts_lists', 'concept_id', 'list_id');
    }
    public function conceptLanguages()
    {
        return $this->hasMany(ConceptLanguage::class, 'concept_id');
    }
    public function conceptErrors()
    {
        return $this->hasMany(ConceptError::class, 'concept_id');
    }
}
