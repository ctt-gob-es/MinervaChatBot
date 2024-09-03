<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class Chatbot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'chatbots';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['name', 'city_councils_id', 'information', 'creator_id', 'active', 'st_training'];

    protected $casts = [
        'id'          => 'string',
        'name'        => 'string',
        'city_councils_id' => 'string',
        'creator_id' => 'integer',
        'information'   => 'string',
        'active'  => 'integer'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            $post->{$post->getKeyName()} = (string) Str::uuid();
        });
    }

    public function cityCouncil()
    {
        return $this->belongsTo(CityCouncils::class, 'city_councils_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function settings()
    {
        return $this->hasMany(ChatbotSetting::class, 'chatbot_id');
    }

    public function logs()
    {
        return $this->hasMany(ChatbotLog::class, 'chatbot_id');
    }

    public function modifications()
    {
        return $this->hasMany(ChatbotModification::class, 'chatbot_id');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'chatbot_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'chatbot_id');
    }

    public function schedule()
    {
        return $this->hasMany(ScheduleDayTimeSlot::class, 'id_chatbot');
    }

    public function ports()
    {
        return $this->hasMany(ChatbotPort::class, 'chatbots_id');
    }

    public function chatbotRasa()
    {
        return $this->hasMany(ChatbotRasa::class, 'chatbot_id');
    }
}
