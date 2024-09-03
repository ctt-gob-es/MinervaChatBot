<?php

namespace Database\Seeders;

use App\Models\TrainingStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TrainingStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TrainingStatus::firstOrCreate(['name' => 'Negativo']);
        TrainingStatus::firstOrCreate(['name' => 'Sin valoración']);
        TrainingStatus::firstOrCreate(['name' => 'Positiva']);
        TrainingStatus::firstOrCreate(['name' => 'Sin categoría']);
    }
}
