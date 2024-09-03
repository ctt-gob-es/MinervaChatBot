<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    public $table = 'time_slots';

    protected $fillable = ['name', 'description'];

    public function dayTimeSlots()
    {
        return $this->hasMany(DayTimeSlot::class, 'id_time_slot');
    }
}
