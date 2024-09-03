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
        Schema::create('day_time_slot', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('id_day')->nullable();
            $table->foreign('id_day')->references('id')->on('days'); 
            $table->unsignedBigInteger('id_time_slot')->nullable(); 
            $table->foreign('id_time_slot')->references('id')->on('time_slots'); 
            $table->time('start_time')->nullable(false); 
            $table->time('end_time')->nullable(false); 
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
        Schema::dropIfExists('day_time_slot');
    }
};
