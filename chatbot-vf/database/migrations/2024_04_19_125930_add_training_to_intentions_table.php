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
        Schema::table('intentions', function (Blueprint $table) {
            $table->boolean('training')->default(false)->after('subjects_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->dropColumn('training');
        });
    }
};
