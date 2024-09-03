<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptList extends Model
{
    use HasFactory;
    protected $table = 'concepts_lists';
    protected $fillable = [
        'concept_id',
        'list_id'
    ];
}
