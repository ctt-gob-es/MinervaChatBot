<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntentionModification extends Model
{
    use HasFactory;

    protected $table = 'intention_modifications';

    const INTENTION_CREATED = 'Intenciòn creado';
    const INTENTION_ENABLED = 'Intenciòn habilitado';
    const INTENTION_DISABLED = 'Intenciòn deshabilitado';
    const INTENTION_DELETED = 'Intenciòn eliminado';

    protected $fillable = [
        'action',
        'intention_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function intention()
    {
        return $this->belongsTo(Intentions::class, 'intention_id');
    }
}
