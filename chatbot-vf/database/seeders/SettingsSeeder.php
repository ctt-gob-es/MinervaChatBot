<?php

namespace Database\Seeders;

use App\Models\CityCouncilSetting;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        // Datos de configuración general
        $generalSettings = [
            ['name' => 'logo', 'value' => null, 'description' => null],
            ['name' => 'color', 'value' => '#327994', 'description' => null],
            ['name' => 'pie_de_página', 'value' => 'Web Desarrollada por el Servicio de Informática de AUNNA IT', 'description' => null],
            ['name' => 'titulo_login', 'value' => "Bienvenidos", 'description' => null],
            ['name' => 'subtitulo_login', 'value' => "Iniciar sesión en su cuenta", 'description' => null],
            ['name' => 'imagen_login', 'value' => null, 'description' => null],
            ['name' => 'recuperar_clave', 'value' => 0, 'description' => null],
        ];

        // Insertar o actualizar registros en la tabla 'settings'
        foreach ($generalSettings as $setting) {
            Setting::updateOrCreate(['name' => $setting['name']], $setting);
        }

        // Obtener los IDs de los ajustes insertados
        $settingIds = Setting::pluck('id')->toArray();

        // Datos de configuración específica de cada ayuntamiento (city_council)
        $cityCouncilSettings = [
            ['setting_id' => $settingIds[0], 'city_council_id' => 1, 'value' => null],
            ['setting_id' => $settingIds[1], 'city_council_id' => 1, 'value' => '#008B74'],
            ['setting_id' => $settingIds[2], 'city_council_id' => 1, 'value' => ''],
        ];

        // Insertar o actualizar registros en la tabla 'city_council_setting'
        foreach ($cityCouncilSettings as $cityCouncilSetting) {
            CityCouncilSetting::updateOrCreate(
                [
                    'setting_id' => $cityCouncilSetting['setting_id'],
                    'city_council_id' => $cityCouncilSetting['city_council_id']
                ],
                $cityCouncilSetting
            );
        }
    }
}
