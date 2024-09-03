<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use App\Models\CityCouncilSetting;


/**
 * @return User
 */
function getLoggedInUser()
{
    return Auth::user();
}

function getLoggedInUserId()
{
    return Auth::id();
}

function getLoggedInUserRole()
{
    return Auth::user()->getRoleNames()->first();
}

function getAvatarUrl()
{
    return 'https://ui-avatars.com/api/';
}

function getRandomColor($userId)
{
    $colors = ['329af0', 'fc6369', 'ffaa2e', '42c9af', '7d68f0'];
    $index = $userId % 5;

    return $colors[$index];
}

function getUserImageInitial($userId, $name)
{
    $user = User::find($userId);

    if ($user->photo) {


        return "/support/userProfile/" . $user->photo;
    } else {
    }

    return getAvatarUrl() . "?name=$name&size=100&rounded=true&color=fff&background=" . getRandomColor($userId);
}


function getColorSetting()
{
    $idCity = session('id_city');
    if ($idCity) {
        $dataCityCouncilSetting = CityCouncilSetting::where('setting_id', 2)
            ->where('city_council_id', $idCity)
            ->value('value');
        if ($dataCityCouncilSetting != null) {
            $colorSetting = $dataCityCouncilSetting;
        } else {
            $colorSetting = Setting::where('name', 'color')->value('value');
        }
    } else {
        $colorSetting = Setting::where('name', 'color')->value('value');
        if (!$colorSetting) {
            $colorSetting = '#327994';
        }
    }

    return $colorSetting;
}



function getImageSetting()
{
    $idCity = session('id_city');
    if ($idCity) {
        $dataCityCouncilSetting = CityCouncilSetting::where('setting_id', 1)
            ->where('city_council_id', $idCity)
            ->value('value');
        if ($dataCityCouncilSetting != null) {
            $imageSetting = "/support/logoCityCouncilSetting/" . $dataCityCouncilSetting;
        } else {
            $imageSetting = Setting::where('name', 'logo')->value('value');
            if (!$imageSetting) {
                $imageSetting = null;
            }
        }
    } else {
        $imageSetting = Setting::where('name', 'logo')->value('value');
        if (!$imageSetting) {
            $imageSetting = null;
        }
    }

    return $imageSetting;
}

function getFooterData()
{
    $idCity = session('id_city');
    if ($idCity) {
        $dataCityCouncilSetting = CityCouncilSetting::where('setting_id', 3)
            ->where('city_council_id', $idCity)
            ->value('value');
        if ($dataCityCouncilSetting != null) {
            $titleSetting = $dataCityCouncilSetting;
        } else {
            $titleSetting = Setting::where('name', 'pie_de_página')->value('value');
        }
    } else {
        $titleSetting = Setting::where('name', 'pie_de_página')->value('value');
        if (!$titleSetting) {
            $titleSetting = 'Info de prueba';
        }
    }

    return $titleSetting;
}


function getCityCouncilsForAdmin()
{
    if (auth()->check()) {
        $user = User::find(auth()->id());
        $roles = $user->getRoleNames();
        if ($roles[0] !== 'SuperAdmin') {
            return \App\Models\ManageClient::join('users', 'users.id', 'manage_clients.user_id')
                ->join('city_councils', 'city_councils.id', 'manage_clients.client_id')
                ->select('city_councils.*')->where('manage_clients.user_id', auth()->id())->get();
        } else {
            return \App\Models\CityCouncils::get();
        }
    } else {
        // Usuario no autenticado
        // Puedes manejar este caso según tus necesidades, por ejemplo, devolver un valor predeterminado o lanzar una excepción.
        return []; // o lanzar una excepción
    }
}


function tituloLoginSetting()
{
    $tituloLoginSetting = Setting::where('name', 'titulo_login')->value('value');

    if ($tituloLoginSetting === null) {
        return "Bienvenidos";
    }

    return $tituloLoginSetting;
}


function subtituloLoginSetting()
{
    $subtituloLoginSetting = Setting::where('name', 'subtitulo_login')->value('value');

    if ($subtituloLoginSetting === null) {
        return "Iniciar sesión en su cuenta";
    }

    return $subtituloLoginSetting;
}

function forgotPasswordSetting()
{
    $state = null;
    $subtituloLoginSetting = Setting::where('name', 'recuperar_clave')->value('value');

    if ($subtituloLoginSetting === null) {
        return "Iniciar sesión en su cuenta";
    }

    if ($subtituloLoginSetting == 1) {
        $state = true;
    } elseif ($subtituloLoginSetting == 0) {
        $state = false;
    } else {
        $state = null;
    }

    return $state;
}

function imagenLoginSetting()
{
    $imagenLoginSetting = Setting::where('name', 'imagen_login')->value('value');

    if ($imagenLoginSetting === null) {
        return null;
    } else {
        return $imagenLoginSetting;
    }
}

function colorLoginSetting()
{
    $colorLoginSetting = Setting::where('name', 'color')->value('value');

    if ($colorLoginSetting === null) {
        return "#327994";
    }

    return $colorLoginSetting;
}
