<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResCombination extends Model
{
    use HasFactory;

    protected $table = 'res_combination';

    protected $fillable = [ 'combination_id', 'concept_id', 'value', 'intentions_id', 'response'];

    public function concept()
    {
        return $this->belongsTo(Concept::class);
    }
    public function intention()
    {
        return $this->belongsTo(Intention::class);
    }
}
