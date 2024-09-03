<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversation_intentions', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->nullable()->change();
            $table->unsignedBigInteger('intention_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_intentions', function (Blueprint $table) {
        });
    }
};
