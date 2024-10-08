<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DefaultSetting extends Model
{
    use HasFactory;

    protected $table = 'default_settings';

    protected $fillable = [
        'name',
        'value',
        'description',
    ];
}

