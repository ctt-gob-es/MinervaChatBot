<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\CityCouncilSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/getSettingsApi",
     *     tags={"settings"},
     *     summary="Obtener configuración ajuste general cliente",
     *     description="Obtiene la configuración de ajuste general de cliente.",
     *     @OA\Response(
     *         response=200,
     *         description="Configuración obtenida exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="key", type="string"),
     *                     @OA\Property(property="value", type="string"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuración no encontrada.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Configuración no encontrada.")
     *         )
     *     )
     * )
     */
    public function getSettings()
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Configuración no encontrada.'], 404);
        try {
            $settings = Setting::all();
            if ($settings) {
                return response()->json(['success' => true, 'data' => $settings], 200);
            } else {
                return $errorMessage;
            }
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }
    /**
     * Actualiza un ajuste específico.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP.
     * @return \Illuminate\Http\Response
     *
     * @OA\Put(
     *     path="/api/updateSettingsApi",
     *     tags={"settings"},
     *     summary="Actualiza un ajuste del sistema",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para actualizar el ajuste",
     *         @OA\JsonContent(
     *             required={"id", "value"},
     *             @OA\Property(property="id", type="integer", description="ID del ajuste"),
     *             @OA\Property(property="value", type="string", description="Nuevo valor del ajuste")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración actualizada correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuración actualizada correctamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuración no encontrada.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Configuración no encontrada.")
     *         )
     *     )
     * )
     */
    public function updateSettings(Request $request)
    {

        $data = $request->all();
        if(isset($data['id'])){
            $id = $data['id'];
        } else {
            return response()->json(['success' => false, 'message' => 'El campo "id" es obligatorio']);
        }
        $setting = Setting::where('id', $id)->first();

        if ($setting) {
            if (isset($data['key']) && isset($data['value'])) {
                if($data['key'] == 'logo' && $data['value'] == null){
                    $data['value'] = '';
                }
            }
            $setting->value = $data['value'];
            $setting->save();

            return response()->json(['success' => true, 'message' => 'Configuración actualizada correctamente.'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'Configuración no encontrada.'], 404);
        }
    }

    /**
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getSettingsDtaApi",
     *     tags={"settings"},
     *     summary="Muestra la configuración del cliente",
     *     @OA\Response(
     *         response=200,
     *         description="Configuración del cliente recuperada exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", description="ID de la configuración del cliente"),
     *                     @OA\Property(property="city_council_id", type="integer", description="ID del cliente"),
     *                     @OA\Property(property="setting_id", type="integer", description="ID del ajuste"),
     *                     @OA\Property(property="value", type="string", description="Valor del ajuste"),
     *                     @OA\Property(property="cityCouncil", type="object",
     *                         @OA\Property(property="id", type="integer", description="ID del cliente"),
     *                         @OA\Property(property="name", type="string", description="Nombre del cliente")
     *                     ),
     *                     @OA\Property(property="setting", type="object",
     *                         @OA\Property(property="id", type="integer", description="ID del ajuste"),
     *                         @OA\Property(property="name", type="string", description="Nombre del ajuste")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado."
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name');

        if ($userRoles->contains('SuperAdmin')) {
            $settings = CityCouncilSetting::with(['cityCouncil:id,name', 'setting:id,name'])
                ->whereIn('id', function ($query) {
                    $query->selectRaw('MAX(id)')
                        ->from('city_council_setting')
                        ->groupBy('city_council_id');
                })
                ->orderByDesc('created_at')
                ->get();
        } elseif (!$userRoles->contains('SuperAdmin')) {

            $cityCouncilId = $request->id_city;
            $settings = CityCouncilSetting::with(['cityCouncil:id,name', 'setting:id,name'])
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('city_council_setting')
                    ->groupBy('city_council_id');
            })
            ->where('city_council_id', $cityCouncilId)
            ->orderByDesc('created_at')
            ->get();

        } else{
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['success' => true, 'data' => $settings], 200);
    }
}
