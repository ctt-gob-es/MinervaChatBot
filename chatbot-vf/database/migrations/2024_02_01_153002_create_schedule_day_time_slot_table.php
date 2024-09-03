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
        Schema::create('schedule_day_time_slot', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_schedule')->nullable()->default(null);
            $table->foreign('id_schedule')->references('id')->on('schedule');
            $table->unsignedBigInteger('id_day_time_slot')->nullable()->default(null);
            $table->foreign('id_day_time_slot')->references('id')->on('day_time_slot');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->softDeletes()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_day_time_slot');
    }
};
