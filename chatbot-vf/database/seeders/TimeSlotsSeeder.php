<?php

namespace Database\Seeders;

use App\Models\TimeSlot;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeSlotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeSlots = [
            [
                'name' => 'franja1',
            ],
            [
                'name' => 'franja2',
            ],
        ];

        foreach ($timeSlots as $slot) {
            $existingSlot = DB::table('time_slots')
                ->where('name', $slot['name'])
                ->first();

            if (!$existingSlot) {
                TimeSlot::create($slot);
            }
        }
    }

}