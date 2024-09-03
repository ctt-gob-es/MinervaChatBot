<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayLanguage extends Model
{
    use HasFactory;

    protected $table = 'holidays_languages';

    protected $fillable = ['holiday_id', 'message'];

    public function chatbotSetting()
    {
        return $this->belongsTo(Holiday::class, 'holiday_id');
    }
}
