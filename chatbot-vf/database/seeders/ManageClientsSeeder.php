<?php

namespace Database\Seeders;

use App\Models\ManageClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ManageClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $clients = DB::table('city_councils')->get();
            foreach ($clients as $client) {
                $this->assignUsersToClient($client->id, 1);
        }
        $this->assignUsersToClient(1, 1);
    }

    private function assignUsersToClient($clientId,$userId): void
    {
        ManageClient::create([
            'client_id' => $clientId,
            'user_id' => $userId,
        ]);
    }
}
