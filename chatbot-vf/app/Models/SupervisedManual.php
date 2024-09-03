<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisedManual extends Model
{
    use HasFactory;
    protected $table = 'supervised_manual';
    protected $fillable = [
        'intention_id',
        'chatbot_id',
        'question',
        'language',
        'manual_rating'
    ];
}
