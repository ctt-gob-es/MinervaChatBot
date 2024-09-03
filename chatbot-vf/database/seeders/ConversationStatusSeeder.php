<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ConversationStatus;

class ConversationStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ConversationStatus::firstOrCreate(['name' => 'En Curso']);
        ConversationStatus::firstOrCreate(['name' => 'Finalizada']);
        ConversationStatus::firstOrCreate(['name' => 'Inactividad']);
        ConversationStatus::firstOrCreate(['name' => 'Abandono']);
    }
}
