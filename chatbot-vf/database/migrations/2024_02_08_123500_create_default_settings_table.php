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
        Schema::create('default_settings', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned(); // Campo ID, autoincremental, primary key
            $table->string('name')->nullable()->default(null);
            $table->text('value')->nullable();
            $table->string('description')->nullable()->default(null); 
            $table->timestamp('created_at')->nullable(false)->default(DB::raw('CURRENT_TIMESTAMP')); // Campo created_at, timestamp not null, default current_timestamp()
            $table->timestamp('updated_at')->nullable(false)->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')); // Campo updated_at, timestamp not null, default current_timestamp() on update current_timestamp()
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('default_settings');
    }
};
