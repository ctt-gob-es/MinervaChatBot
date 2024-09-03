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
        Schema::create('conversation_intentions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('intention_id')->nullable();
            $table->text('question')->nullable();
            $table->text('answer')->nullable();
            $table->integer('result')->default(0);
            $table->enum('manual_rating', ['Descartada'])->nullable(); // Valoración manual puede ser nula
            $table->timestamps();

            // Definir las claves foráneas
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('cascade');
            $table->foreign('intention_id')->references('id')->on('intentions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_intentions');
    }
};
