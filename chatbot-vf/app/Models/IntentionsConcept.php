<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentionsConcept extends Model
{
    use HasFactory;
    protected $table = 'intentions_concepts';
    protected $fillable = [
        'intention_id',
        'concept_id'
    ];
}
