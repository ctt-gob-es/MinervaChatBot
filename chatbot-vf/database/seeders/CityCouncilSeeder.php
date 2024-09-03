<?php

namespace Database\Seeders;

use App\Models\CityCouncils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CityCouncilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cityCouncils = [
            ['name' => 'Elche', 'information' => 'Ayuntamiento de Elche', 'creator_id' => 1],
        ];

        foreach ($cityCouncils as $cityCouncil) {
            $existingCouncil = DB::table('city_councils')
                ->where('name', $cityCouncil['name'])
                ->where('creator_id', $cityCouncil['creator_id'])
                ->first();

            if (!$existingCouncil) {
                CityCouncils::create($cityCouncil);
            }
        }
    }
}
