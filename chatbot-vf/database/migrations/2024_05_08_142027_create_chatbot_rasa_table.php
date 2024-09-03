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
        Schema::create('chatbot_rasa', function (Blueprint $table) {

            $table->id();
            $table->uuid('chatbot_id');
            $table->text('text')->nullable();
            $table->string('intention')->nullable();
            $table->json('slots')->nullable();
            $table->string('user')->nullable();
            $table->string('form')->nullable();
            $table->string('question_citizen')->nullable();
            $table->string('validate_response')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('chatbot_id')->references('id')->on('chatbots')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_rasa');
    }
};
