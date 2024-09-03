<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DaysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            [
                'day' => 'lunes',
            ],
            [
                'day' => 'martes',
            ],
            [
                'day' => 'miercoles',
            ],
            [
                'day' => 'jueves',
            ],
            [
                'day' => 'viernes',
            ],
            [
                'day' => 'sabado',
            ],
            [
                'day' => 'domingo',
            ],
        ];

        foreach ($days as $day) {
            $existingDay = DB::table('days')
                ->where('day', $day['day'])
                ->first();

            if (!$existingDay) {
                DB::table('days')->insert($day);
            }
        }
    }

}