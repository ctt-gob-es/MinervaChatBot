<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $password = Hash::make('Admin-1234');
        $inputs = [
            [
                'name' => 'devAunna SuperAdmin',
                'email' => 'dev@aunnait.es',
                'email_verified_at' => \Carbon\Carbon::now(),
                'password' => $password,
            ],

            [
                'name' => 'Api',
                'email' => 'api@api.es',
                'email_verified_at' => \Carbon\Carbon::now(),
                'password' => bcrypt('Api-1234'),
            ]
        ];

        foreach ($inputs as $index => $input) {
            User::updateOrCreate(['email' => $input['email']], $input);

            $user = User::where('email', $input['email'])->first();

            switch ($index) {
                case 0:
                    $user->assignRole('SuperAdmin');
                    break;
                case 1:
                    $user->assignRole('Api');
                    break;
            }
        }
    }
}
