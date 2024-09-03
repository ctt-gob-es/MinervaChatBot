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
        Schema::create('nodes', function (Blueprint $table) {
            $table->id();
            $table->integer('node');
            $table->string('name');
            $table->string('text')->nullable();
            $table->string('class');
            $table->string('html');
            $table->string('typenode')->nullable();
            $table->foreignId('chatbot_log_id')->constrained('chatbot_logs');
            $table->integer('deleted')->default(0);
            $table->integer('end')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes');
    }
};
