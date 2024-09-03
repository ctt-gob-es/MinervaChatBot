<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptError extends Model
{
    use HasFactory;

    protected $fillable = [
        'language',
        'answer',
        'concept_id',
    ];

    /**
     * Get the concept that owns the error.
     */
    public function concept()
    {
        return $this->belongsTo(Concept::class);
    }
}
