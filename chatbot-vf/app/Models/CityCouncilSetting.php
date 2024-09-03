<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CityCouncils;

class CityCouncilSetting extends Model
{
    use HasFactory;

    protected $table = 'city_council_setting';
    protected $fillable = ['setting_id', 'city_council_id', 'value'];

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }

    public function cityCouncil()
    {
        return $this->belongsTo(CityCouncils::class, 'city_council_id');
    }
}
