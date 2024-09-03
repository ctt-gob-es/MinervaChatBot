<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Chatbot;
use Illuminate\Http\Request;
use App\Models\ChatbotSetting;
use App\Models\DefaultSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DefaultSettingController extends Controller
{
    public function getDefaults()
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado'], 404);

        try {
            $defaultSetting = DefaultSetting::all();
            return response()->json(['success' => true, 'data' => $defaultSetting], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['message' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }
    /**
     * Almacena una nueva configuración por defecto.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP.
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/storeDefaultApi",
     *     tags={"settings"},
     *     summary="Almacena una nueva configuración por defecto",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para crear la configuración por defecto",
     *         @OA\JsonContent(
     *             required={"type", "name", "value"},
     *             @OA\Property(property="type", type="string", description="Tipo de configuración"),
     *             @OA\Property(property="name", type="string", description="Nombre de la configuración"),
     *             @OA\Property(property="value", type="string", description="Valor de la configuración")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Configuración por defecto creada exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuración por defecto creada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado al crear la configuración por defecto.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado.'], 404);

        DB::beginTransaction();

        try {

            $defaultSetting = new DefaultSetting();
            $defaultSetting->type = $request->input('type');

            $name = str_replace(' ', '_', $request->input('name'));
            $defaultSetting->name = $name;

            $defaultSetting->value = $request->input('value');
            $defaultSetting->save();

            $currentDateTime = Carbon::now();
            $chatbots = Chatbot::all();

            foreach ($chatbots as $chatbot) {
                $chatbotSetting = new ChatbotSetting();

                $chatbotSetting->chatbot_id = $chatbot->id;
                $chatbotSetting->default_id = $defaultSetting->id;
                $chatbotSetting->value = $defaultSetting->value;
                $chatbotSetting->deleted_at = $currentDateTime;

                $chatbotSetting->save();
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Configuración por defecto creada.', 'data' => $defaultSetting], 202);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }


    /**
     * Actualiza una configuración por defecto específica.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP.
     * @param string $id El ID de la configuración por defecto a actualizar.
     * @return \Illuminate\Http\Response
     *
     * @OA\Put(
     *     path="/api/updateDefaultApi/{id}",
     *     tags={"settings"},
     *     summary="Actualiza una configuración por defecto específica",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la configuración por defecto a actualizar",
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos para actualizar la configuración por defecto",
     *         @OA\JsonContent(
     *             required={"value"},
     *             @OA\Property(property="value", type="string", description="Nuevo valor de la configuración por defecto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración por defecto actualizada correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuración por defecto actualizada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado al actualizar la configuración por defecto.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {

        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado.'], 404);
        try {

            $data = $request->all();
            $defaultSetting = DefaultSetting::findOrFail($id);
            if ($defaultSetting) {
                $language = true;
                if ($defaultSetting->type == 'idioma' && $data['value'] == 0) {
                    $languages = $this->checkDefaultLanguages();
                    $default = $this->checkDefault();
                    if ($languages == false) {
                        return response()->json(['success' => false, 'message' => 'No puedes eliminar todos los lenguajes del Chatbot. Debe tener al menos 1.'], 200);
                    }
                    if ($default == $defaultSetting->name) {
                        return response()->json(['success' => false, 'message' => 'No puedes eliminar el lenguaje que tienes como principal'], 200);
                    }
                }
            }
            if (isset($data['key']) && isset($data['value'])) {
                if ($data['key'] == 'logo' && $data['value'] == null) {
                    $data['value'] = '';
                }
            }
            $defaultSetting->value = $data['value'];
            $defaultSetting->save();

            return response()->json(['success' => true, 'message' => 'Configuración por defecto actualizada.'], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    public function destroy(string $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado.'], 404);

        try {
            $defaultSetting = DefaultSetting::find($id);
            if (!$defaultSetting) {
                return $errorMessage;
            }
            $defaultSetting->delete();
            return response()->json(['success' => true, 'message' => 'Configuración por defecto eliminada'], 200);
        } catch (\Throwable $th) {
            return $errorMessage;
        }
    }

    public function checkDefaultLanguages()
    {

        $defaultSettings = DefaultSetting::where('type', 'idioma')
            ->where('value', 1)
            ->get();

        if (count($defaultSettings) > 1) {
            return true;
        } else {
            return false;
        }
    }

    public function checkDefault()
    {
        $defaultLanguage = DefaultSetting::where('type', 'predeterminado')
            ->first();

        return $defaultLanguage->value;
    }
}
