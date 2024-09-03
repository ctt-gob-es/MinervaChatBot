<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentionLanguage extends Model
{
    use HasFactory;
    protected $table = 'intention_languages';
    protected $fillable = [
        'name',
        'language',
        'intention_id',
    ];
}
