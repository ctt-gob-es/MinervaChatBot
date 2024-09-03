<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lists extends Model
{
    use HasFactory;

    protected $table = 'lists'; // Especifica el nombre de la tabla

    protected $fillable = ['name', 'chatbot_id']; // Especifica los campos que pueden ser asignados masivamente

    public function terms()
    {
        return $this->hasMany(ListTerm::class, 'list_id', 'id');
    }

    public function concepts()
    {
        return $this->belongsToMany(Concept::class, 'concepts_lists', 'concept_id', 'list_id');
    }

}
