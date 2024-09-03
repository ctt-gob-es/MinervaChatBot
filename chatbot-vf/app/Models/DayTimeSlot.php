<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DayTimeSlot extends Model
{
    use HasFactory;

    public $table = 'day_time_slot';

    protected $fillable = ['id_day', 'id_time_slot', 'start_time', 'end_time'];

    public function day()
    {
        return $this->belongsTo(Day::class, 'id_day');
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'id_time_slot');
    }

    public function scheduleDayTimeSlots()
    {
        return $this->hasMany(ScheduleDayTimeSlot::class, 'id_day_time_slot');
    }
}
