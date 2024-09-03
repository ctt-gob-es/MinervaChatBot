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
        Schema::create('concept_languages', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->string('language');
            $table->unsignedBigInteger('concept_id')->nullable();
            $table->timestamps();
            $table->foreign('concept_id')->references('id')->on('concepts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concept_languages');
    }
};
