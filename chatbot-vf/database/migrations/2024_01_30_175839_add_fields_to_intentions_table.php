<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->boolean('validated')->nullable()->after('information');
            $table->enum('creation_method', ['WEB', 'API', 'IMPORT'])->nullable()->after('validated');
            $table->unsignedBigInteger('creator')->nullable()->after('creation_method');
            $table->foreign('creator')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('intentions', function (Blueprint $table) {
            $table->dropForeign(['creator']);
            $table->dropColumn('validated');
            $table->dropColumn('creation_method');
            $table->dropColumn('creator');
        });
    }
};
