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
        Schema::table('intentions', function (Blueprint $table) {
            $table->char('chatbot_id', 36)->after('creator');
            $table->foreign('chatbot_id')->references('id')->on('chatbots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->dropForeign(['chatbot_id']);
            $table->dropColumn('chatbot_id');
        });
    }
};
