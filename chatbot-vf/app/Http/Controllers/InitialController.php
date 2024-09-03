<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InitialController extends Controller
{
    public function saveSelection(Request $request)
    {
        $idCity = $request->input('id_city');
        session(['id_city' => $idCity]);
    }
}
