<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SynonymTerm extends Model
{
    use HasFactory;

    protected $fillable = ['synonym_id', 'term', 'language', 'term_id'];

    public function synonym()
    {
        return $this->belongsTo(Synonym::class, 'synonym_id');
    }

    public function listTerm()
    {
        return $this->belongsTo(ListTerm::class, 'term_id');
    }
}
