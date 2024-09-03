<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Validator;
use App\Models\Chatbot;
use App\Models\Subject;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Validator::extend('unique_chatbot_name_in_city_council', function ($attribute, $value, $parameters, $validator) {
            $cityCouncilId = $parameters[0] ?? null;

            return !Chatbot::where('name', $value)->where('city_councils_id', $cityCouncilId)->exists();
        });
        Validator::extend('unique_subject_name_in_chatbot', function ($attribute, $value, $parameters, $validator) {
            $chatbotId = $parameters[0] ?? null;

            return !Subject::where('name', $value)->where('chatbot_id', $chatbotId)->exists();
        });
    }
}
