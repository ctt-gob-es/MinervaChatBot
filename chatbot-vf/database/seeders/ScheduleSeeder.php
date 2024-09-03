<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = [
            [
                'active' => 0,
                'type' => 'normal'
            ],
            [
                'active' => 0,
                'type' => 'special'
            ],
            [
                'active' => 1,
                'type' => 'normal'
            ],
            [
                'active' => 1,
                'type' => 'special'
            ]
        ];

        foreach ($schedules as $schedule) {
            Schedule::create($schedule);
        }
    }
}

