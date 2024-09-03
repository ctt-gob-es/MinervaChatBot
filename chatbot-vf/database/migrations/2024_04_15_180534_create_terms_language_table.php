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
        Schema::create('terms_language', function (Blueprint $table) {
            $table->id();
            $table->string('language');
            $table->string('lang_term');
            $table->unsignedBigInteger('list_term_id');
            $table->timestamps();
            $table->foreign('list_term_id')->references('id')->on('list_terms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_language');
    }
};
