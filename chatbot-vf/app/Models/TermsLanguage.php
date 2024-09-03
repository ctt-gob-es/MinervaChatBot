<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsLanguage extends Model
{
    use HasFactory;
    protected $table = 'terms_language';

    protected $fillable = ['language', 'lang_term', 'list_term_id'];
}
