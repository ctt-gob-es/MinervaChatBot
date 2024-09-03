<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CityCouncilSetting;
use Illuminate\Database\Eloquent\SoftDeletes;


class CityCouncils extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'city_councils';

    protected $fillable = ['name', 'information','creator_id'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'manage_id');
    }

    public function settings()
    {
        return $this->hasMany(CityCouncilSetting::class, 'city_council_id');
    }

}
