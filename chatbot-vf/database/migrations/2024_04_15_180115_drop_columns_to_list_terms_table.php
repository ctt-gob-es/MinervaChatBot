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
        Schema::table('list_terms', function (Blueprint $table) {
            $table->dropColumn('language');
            $table->dropColumn('lang_term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('list_terms', function (Blueprint $table) {
            $table->string('language')->after('term');
            $table->string('lang_term')->after('language');
        });
    }
};
