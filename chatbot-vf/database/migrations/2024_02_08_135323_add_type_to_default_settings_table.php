<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('default_settings', function (Blueprint $table) {
            $table->string('type')->nullable()->after('value')->default('string')->comment('Type of the setting: string, boolean, number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('default_settings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
