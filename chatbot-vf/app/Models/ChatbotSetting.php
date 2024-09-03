<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\DefaultSetting;
use App\Models\Chatbot;
use App\Models\ChatbotSettingLanguage;

class ChatbotSetting extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'chatbot_settings';

    protected $fillable = ['value', 'chatbot_id', 'default_id'];

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class, 'chatbot_id');
    }

    public function defaultTable()
    {
        return $this->belongsTo(DefaultSetting::class, 'default_id');
    }
    public function languages()
    {
        return $this->hasMany(ChatbotSettingLanguage::class, 'chatbot_setting_id');
    }
}

