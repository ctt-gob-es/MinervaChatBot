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
        Schema::create('supervised_manual', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intention_id')->nullable();
            $table->char('chatbot_id', 36);
            $table->text('question');
            $table->text('language');
            $table->enum('manual_rating', ['Descartada'])->nullable();
            $table->timestamps();
            $table->foreign('intention_id')->references('id')->on('intentions');
            $table->foreign('chatbot_id')->references('id')->on('chatbots');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervised_manual');
    }
};
