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
        Schema::create('intention_languages', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->string('language');
            $table->unsignedBigInteger('intention_id')->nullable();
            $table->timestamps();
            $table->foreign('intention_id')->references('id')->on('intentions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intention_languages');
    }
};
