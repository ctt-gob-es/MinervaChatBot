<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_day_time_slot', function (Blueprint $table) {
            $table->char('id_chatbot', 36)->after('id');
            $table->foreign('id_chatbot')->references('id')->on('chatbots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_day_time_slot', function (Blueprint $table) {
            $table->dropForeign(['id_chatbot']);
            $table->dropColumn('id_chatbot');
        });
    }
};
