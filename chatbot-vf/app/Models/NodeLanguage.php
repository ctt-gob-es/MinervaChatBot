<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeLanguage extends Model
{
    use HasFactory;

    protected $table = 'node_languages';

    protected $fillable = [
        'text',
        'language',
        'node_id',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }
}
