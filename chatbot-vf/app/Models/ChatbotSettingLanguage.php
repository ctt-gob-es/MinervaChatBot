<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatbotSettingLanguage extends Model
{
    use HasFactory;

    protected $table = 'chatbot_settings_language';

    protected $fillable = ['chatbot_setting_id', 'language', 'value'];

    public function chatbotSetting()
    {
        return $this->belongsTo(ChatbotSetting::class, 'chatbot_setting_id');
    }
}
