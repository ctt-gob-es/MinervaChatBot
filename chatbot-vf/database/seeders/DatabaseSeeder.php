<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CreatePermissionSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(CityCouncilSeeder::class);
        $this->call(ManageClientsSeeder::class);
        $this->call(DefaultSettingsSeeder::class);
        $this->call(SettingsSeeder::class);
        $this->call(TrainingStatusSeeder::class);
        $this->call(ConversationStatusSeeder::class);
        $this->call(DaysSeeder::class);
        $this->call(TimeSlotsSeeder::class);
        $this->call(ScheduleSeeder::class);
    }
}
