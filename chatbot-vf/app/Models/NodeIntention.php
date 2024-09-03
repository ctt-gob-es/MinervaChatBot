<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NodeIntention extends Model
{
    use HasFactory;

    protected $table = 'node_intentions';

    protected $fillable = [
        'node_id',
        'intention_id',
    ];

    public function node()
    {
        return $this->belongsTo(Node::class);
    }

    public function intention()
    {
        return $this->belongsTo(Intentions::class);
    }
}
