<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;

    public $table = 'days';

    protected $fillable = ['day'];

    public function dayTimeSlots()
    {
        return $this->hasMany(DayTimeSlot::class, 'id_day');
    }

    public function scheduleDayTimeSlots()
    {
        return $this->hasManyThrough(ScheduleDayTimeSlot::class, DayTimeSlot::class, 'id_day', 'id_day_time_slot');
    }
}
