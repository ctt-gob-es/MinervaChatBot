<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CityCouncilSetting;
use App\Models\CityCouncils;
use App\Models\Setting;


class cityCouncilSettingController extends Controller
{
    /**
     * Guarda la configuración de ajustes del cliente.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP.
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/saveSettingsApi",
     *     tags={"settings"},
     *     summary="Guarda la configuración de ajustes del cliente.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de configuración de ajustes del cliente.",
     *         @OA\JsonContent(
     *             required={"city_council_id"},
     *             @OA\Property(property="city_council_id", type="integer", description="ID del cliente"),
     *             @OA\Property(property="logo", type="string", format="binary", description="Archivo del logo en formato de imagen"),
     *             @OA\Property(property="color", type="string", description="Color del cliente"),
     *             @OA\Property(property="pie_de_página", type="string", description="Texto del pie de página")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración del cliente guardada exitosamente."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Los datos proporcionados son incorrectos o incompletos."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ha ocurrido un error al intentar guardar la configuración del cliente."
     *     )
     * )
     */
    public function saveSettings(Request $request)
    {
        try {
            $settingIds = Setting::select('id', 'name', 'value')->get();
            $customerExists = CityCouncils::where('id', $request->city_council_id)->exists();

            if(!$customerExists){
                return response()->json(['success' => false, 'message' => 'El id de cliente proporcionado no es valido.']);
            }

            foreach ($settingIds as $key => $value) {
                $cityCouncilSetting = new CityCouncilSetting();

                $cityCouncilSetting->setting_id = $value['id'];
                $cityCouncilSetting->city_council_id = $request->city_council_id;
                if ($value['name'] === 'logo') {
                    if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                        $img = $request->file('logo');
                        $fileImg = $img->getClientOriginalExtension();
                        $name_img = date('Y_m_d_H_i_s') . '_logo_' . $request->city_council_id . '.' . $fileImg;
                        $img->move(public_path('support/logoCityCouncilSetting'), $name_img);
                        $cityCouncilSetting->value = $name_img;
                    } else {
                        $cityCouncilSetting->value = $value['value'];
                    }
                }

                if ($value['name'] === 'color') {
                    if ($request->color) {
                        $cityCouncilSetting->value = $request->color;
                    } else {
                        $cityCouncilSetting->value = $value['value'];
                    }
                }
                if ($value['name'] === 'pie_de_página') {
                    if ($request->pie_de_página) {
                        $cityCouncilSetting->value = $request->pie_de_página;
                    } else {
                        $cityCouncilSetting->value = $value['value'];
                    }
                }
                $cityCouncilSetting->save();
            }

            return response()->json(['success' => true, 'message' => 'Configuración guardada correctamente!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to save settings', 'error' => $e->getMessage()], 500);
        }
    }

    public function edit(string $id)
    {
        try {
            $cityCouncilSetting = CityCouncilSetting::with('setting')->where('city_council_id', $id)->get();

            if ($cityCouncilSetting->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'La configuración del cliente no fue encontrada.'], 404);
            }
            return response()->json(['success' => true, 'data' => $cityCouncilSetting], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch CityCouncilSetting', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Actualiza la configuración del cliente.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP.
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/updateSettingsCityCouncilApi",
     *     tags={"settings"},
     *     summary="Actualiza la configuración del cliente",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de configuración del cliente",
     *         @OA\JsonContent(
     *             required={"city_council_id"},
     *             @OA\Property(property="city_council_id", type="integer", description="ID del cliente"),
     *             @OA\Property(property="logo", type="string", format="binary", description="Archivo del logo en formato de imagen"),
     *             @OA\Property(property="color", type="string", description="Color del cliente"),
     *             @OA\Property(property="pie_de_página", type="string", description="Texto del pie de página")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración del cliente actualizada exitosamente."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Los datos proporcionados son incorrectos o incompletos."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="La configuración del cliente no fue encontrada."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ha ocurrido un error al intentar actualizar la configuración del cliente."
     *     )
     * )
     */
    public function updateSettingsCityCouncil(Request $request)
    {
        try {
            $settings = $request->only(['logo', 'color', 'pie_de_página']);

            $customerExists = CityCouncils::where('id', $request->city_council_id)->exists();

            if(!$customerExists){
                return response()->json(['success' => false, 'message' => 'El id de cliente proporcionado no es valido.']);
            }

            foreach ($settings as $name => $value) {
                $cityCouncilSetting = CityCouncilSetting::whereHas('setting', function ($query) use ($name) {
                    $query->where('name', $name);
                })->where('city_council_id', $request->city_council_id)->first();

                if ($cityCouncilSetting) {
                    if ($name === 'logo') {
                        if ($request->hasFile('logo')) {
                            $oldLogo = $cityCouncilSetting->value;
                            if ($oldLogo && file_exists(public_path('support/logoCityCouncilSetting/' . $oldLogo))) {
                                unlink(public_path('support/logoCityCouncilSetting/' . $oldLogo));
                            }

                            $img = $request->file('logo');
                            $fileImg = $img->getClientOriginalExtension();
                            $name_img = date('Y_m_d_H_i_s') . '_logo_' . $request->city_council_id . '.' . $fileImg;
                            $img->move(public_path('support/logoCityCouncilSetting'), $name_img);
                            $cityCouncilSetting->value = $name_img;
                        }
                    } else {
                        $cityCouncilSetting->value = $value;
                    }
                    $cityCouncilSetting->save();
                } else {
                    return response()->json(['success' => false, 'message' => 'Setting not found: ' . $name], 404);
                }
            }

            return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to update settings', 'error' => $e->getMessage()], 500);
        }
    }
    /**
     * Elimina la configuración de ajustes del cliente.
     *
     * @param int $cityCouncilId El ID del cliente.
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteSettingsApi/{cityCouncilId}",
     *     tags={"settings"},
     *     summary="Elimina la configuración de ajustes del cliente.",
     *     @OA\Parameter(
     *         name="cityCouncilId",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registros eliminados correctamente."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="La configuración del cliente no fue encontrada."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ha ocurrido un error al intentar eliminar la configuración del cliente."
     *     )
     * )
     */
    public function deleteSettings($cityCouncilId)
    {
        try {
            $cityCouncilSettExist = CityCouncilSetting::where('city_council_id', $cityCouncilId)->exists();

            if(!$cityCouncilSettExist){
                return response()->json(['success'=> false, 'message' => 'El id proporcionado para configuración de cliente no es valido.']);
            }
            CityCouncilSetting::where('city_council_id', $cityCouncilId)->delete();
            return response()->json(['message' => 'Registros eliminados correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al eliminar registros: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Guarda o actualiza la configuración del cliente.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/saveOrUpdateSettingsApi",
     *     tags={"settings"},
     *     summary="Guardar o actualizar configuración",
     *     description="Guarda o actualiza la configuración del cliente, como el logo, color y pie de página.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de configuración del cliente",
     *         @OA\JsonContent(
     *             required={"city_council_id"},
     *             @OA\Property(property="city_council_id", type="integer", description="ID del cliente"),
     *             @OA\Property(property="logo", type="file", description="Archivo de imagen para el logo"),
     *             @OA\Property(property="color", type="string", description="Color de tema para el cliente"),
     *             @OA\Property(property="pie_de_página", type="string", description="Pie de página para el cliente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración guardada o actualizada exitosamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", default="true", description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", default="Settings saved or updated successfully", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuración no encontrada.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", default="false", description="Indica si hubo un error"),
     *             @OA\Property(property="message", type="string", default="Setting not found: {nombre_del_setting}", description="Mensaje de error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", default="false", description="Indica si hubo un error"),
     *             @OA\Property(property="message", type="string", default="Failed to save or update settings", description="Mensaje de error"),
     *             @OA\Property(property="error", type="string", default="Mensaje de error detallado", description="Detalle del error")
     *         )
     *     )
     * )
     */
    public function saveOrUpdateSettings(Request $request)
    {
        try {
            $settings = $request->only(['logo', 'color', 'pie_de_página']);
            $cityCouncilId = $request->city_council_id;

            foreach ($settings as $name => $value) {
                $cityCouncilSetting = CityCouncilSetting::whereHas('setting', function ($query) use ($name) {
                    $query->where('name', $name);
                })->where('city_council_id', $cityCouncilId)->first();

                if (!$cityCouncilSetting) {
                    // Si no existe, crea una nueva configuración
                    $setting = Setting::where('name', $name)->first();
                    if (!$setting) {
                        return response()->json(['success' => false, 'message' => 'Setting not found: ' . $name], 404);
                    }
                    $cityCouncilSetting = new CityCouncilSetting();
                    $cityCouncilSetting->setting_id = $setting->id;
                    $cityCouncilSetting->city_council_id = $cityCouncilId;
                }

                // Actualiza el valor según sea necesario
                if ($name === 'logo' && $request->hasFile('logo')) {
                    $oldLogo = $cityCouncilSetting->value;
                    if ($oldLogo && file_exists(public_path('support/logoCityCouncilSetting/' . $oldLogo))) {
                        unlink(public_path('support/logoCityCouncilSetting/' . $oldLogo));
                    }

                    $img = $request->file('logo');
                    $fileImg = $img->getClientOriginalExtension();
                    $name_img = date('Y_m_d_H_i_s') . '_logo_' . $cityCouncilId . '.' . $fileImg;
                    $img->move(public_path('support/logoCityCouncilSetting'), $name_img);
                    $cityCouncilSetting->value = $name_img;
                } elseif ($name === 'color' && $request->has('color')) {
                    $cityCouncilSetting->value = $request->color;
                } elseif ($name === 'pie_de_página' && $request->has('pie_de_página')) {
                    $cityCouncilSetting->value = $request->pie_de_página;
                }

                $cityCouncilSetting->save();
            }

            return response()->json(['success' => true, 'message' => 'Settings saved or updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to save or update settings', 'error' => $e->getMessage()], 500);
        }
    }
}
