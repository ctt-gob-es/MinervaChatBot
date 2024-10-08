<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingStatus extends Model
{
    use HasFactory;
    protected $table = 'training_status';

    protected $fillable = ['name'];

}
