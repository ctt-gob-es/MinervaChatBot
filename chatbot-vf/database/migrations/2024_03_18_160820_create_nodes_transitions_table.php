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
        Schema::create('nodes_transitions', function (Blueprint $table) {
            $table->id();
            $table->integer('origin');
            $table->string('transition');
            $table->integer('destination');
            $table->integer('deleted')->default(0);
            $table->foreignId('chatbot_log_id')->constrained('chatbot_logs');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nodes_transitions');
    }
};
