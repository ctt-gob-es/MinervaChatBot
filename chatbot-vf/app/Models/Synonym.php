<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Synonym extends Model
{
    use HasFactory;

    protected $table = 'synonyms';

    protected $fillable = ['synonym'];

    public function terms()
    {
        return $this->hasMany(SynonymTerm::class, 'synonym_id');
    }
}

