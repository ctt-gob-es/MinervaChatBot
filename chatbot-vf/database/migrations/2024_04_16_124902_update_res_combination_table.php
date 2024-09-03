<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('res_combination', function (Blueprint $table) {
            $table->tinyInteger('response')->default(0)->change();
            $table->foreignId('intentions_id')->after('value')->constrained('intentions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('res_combination', function (Blueprint $table) {
            $table->tinyInteger('response')->nullable()->change();
            $table->dropForeign(['intentions_id']);
            $table->dropColumn('intentions_id');
        });
    }
};
