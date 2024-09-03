<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $table = 'holidays';

    protected $fillable = [
        'day',
        'name',
        'description',
        'chatbot_id'
    ];

    public function chatbot() {
        return $this->belongsTo(Chatbot::class);
    }
    public function languages()
    {
        return $this->hasMany(HolidayLanguage::class, 'holiday_id');
    }
}
