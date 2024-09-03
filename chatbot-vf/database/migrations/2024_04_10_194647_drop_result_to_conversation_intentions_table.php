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
        Schema::table('conversation_intentions', function (Blueprint $table) {
            $table->dropColumn('result');
            $table->unsignedBigInteger('training_status_id')->nullable()->after('manual_rating');
            $table->foreign('training_status_id')->references('id')->on('training_status');
            $table->enum('type', ['abierta', 'cerrada'])->after('training_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_intentions', function (Blueprint $table) {
            $table->integer('result')->default(0);
            $table->dropForeign(['training_status_id']);
            $table->dropColumn(['training_status_id', 'type']);
        });
    }
};
