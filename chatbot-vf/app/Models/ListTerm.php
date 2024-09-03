<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ListTerm extends Model
{
    use HasFactory;

    protected $table = 'list_terms';

    protected $fillable = ['list_id', 'term'];

    public function list()
    {
        return $this->belongsTo(Lists::class);
    }
    public function synonyms()
    {
        return $this->hasMany(SynonymTerm::class, 'term_id');
    }
    public function terms_lang()
    {
        return $this->hasMany(TermsLanguage::class);
    }
}
