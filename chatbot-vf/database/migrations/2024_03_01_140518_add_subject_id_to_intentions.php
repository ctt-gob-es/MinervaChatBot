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
            $table->foreignId('subjects_id')->after('chatbot_id')->constrained('subjects');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->dropForeign(['subjects_id']);
        });
    }
};
