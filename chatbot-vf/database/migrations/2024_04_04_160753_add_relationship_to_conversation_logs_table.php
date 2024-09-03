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
        Schema::table('conversation_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('node_id')->nullable()->after('type_user');
            $table->foreign('node_id')->references('id')->on('nodes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversation_logs', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
            $table->dropColumn('node_id');
        });
    }
};
