<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DayTimeSlot;
use App\Models\Schedule;

class ScheduleDayTimeSlot extends Model
{
    use HasFactory;

    public $table = 'schedule_day_time_slot';

    protected $fillable = ['id_chatbot', 'id_schedule', 'id_day_time_slot', 'deleted_at'];

    public function dayTimeSlot()
    {
        return $this->belongsTo(DayTimeSlot::class, 'id_day_time_slot');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'id_schedule');
    }
}

