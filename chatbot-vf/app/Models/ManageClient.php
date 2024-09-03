<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManageClient extends Model
{
    use HasFactory;
    protected $table = 'manage_clients';
    protected $fillable = [
        'client_id',
        'user_id'
    ];
}
