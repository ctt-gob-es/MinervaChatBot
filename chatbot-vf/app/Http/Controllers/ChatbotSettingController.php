<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatbotSetting;
use App\Models\ChatbotPort;
use App\Models\Chatbot;
use App\Models\ChatbotSettingLanguage;
use Aunnait\Rasalicante\RasaBotControl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ChatbotSettingController extends Controller
{
    /**
     * Obtiene la configuración de un chatbot por su ID.
     *
     * @param string $id ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getOneChatbotSettingsApi/{id}",
     *     tags={"chatbot_settings"},
     *     summary="Obtiene la configuración de un chatbot por su ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del chatbot, en la URL",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Se devuelven la configuración del chatbot"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo encontrar la configuración"
     *     )
     * )
     */
    public function getOneChatbotSettings($id)
    {

        $errorMessage = response()->json(['success' => false, 'message' => 'No se pudo encontrar la configuración.'], 500);

        $chatbotExist = Chatbot::where('id', $id)->exists();
        if(!$chatbotExist){
            return response()->json(['success' => false, "message"=> 'El id de chatbot es invalido'], 404);
        }

        try {
            $settings = ChatbotSetting::with('chatbot', 'defaultTable', 'languages')
                ->withTrashed()
                ->where('chatbot_id', $id)
                ->get();
            if ($settings->isNotEmpty()) {
                return $settings;
            } else {
                $settings = [];
                return $settings;
            }
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    public function getModuleAgent($id)
    {

        $errorMessage = response()->json(['success' => false, 'message' => 'No se pudo encontrar la configuración.'], 404);

        try {
            $moduleAgent = ChatbotSetting::with('defaultTable')
                ->where('chatbot_id', $id)
                ->where('deleted_at', null)
                ->where('value', 1)
                ->whereHas('defaultTable', function ($query) {
                    $query->where('name', 'modulo_agente');
                })
                ->get();


            if ($moduleAgent->isNotEmpty()) {
                return response()->json(['success' => true, 'message' => 'Modulo activo.'], 200);
            } else {
                $moduleAgent = null;
                return response()->json(['success' => false, 'message' => 'Modulo inactivo.'], 200);
            }
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Actualiza la configuración de un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id ID de la configuración del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/updateChatbotSettingApi/{id}",
     *     tags={"chatbot_settings"},
     *     summary="Actualiza la configuración de un chatbot",
     *     description="Este endpoint permite actualizar la configuración de un chatbot existente en la base de datos. El usuario debe proporcionar los datos necesarios para la actualización, incluyendo una lista de idiomas opcional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la configuración del chatbot, en la url.",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la configuración del chatbot a actualizar",
     *         @OA\JsonContent(
     *             required={"value"},
     *             @OA\Property(property="value", type="string", description="Nuevo valor de la configuración", example="Nuevo valor"),
     *             @OA\Property(property="languages", type="array", description="Lista de idiomas y sus valores (solo para configuraciones de tipo 'mensaje')",
     *                 @OA\Items(
     *                     @OA\Property(property="language", type="string", description="Código del idioma", example="castellano"),
     *                     @OA\Property(property="value", type="string", description="Valor del idioma", example="Nuevo valor")
     *                 )
     *             ),
     *             @OA\Property(property="key", type="string", description="Clave de la configuración", example="titulo")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración actualizada correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Configuración actualizada correctamente."),
     *             @OA\Property(property="module_agent", type="boolean", example=null)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Configuración no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Configuración no encontrada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.")
     *         )
     *     )
     * )
     */

    public function updateChatbotSetting(Request $request, string $id)
    {
        // $data = $request->all();
        // dd($data);
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado.'], 500);
        try {
            $module_agente = null;
            $data = $request->all();

            $rules = [
                'value' => 'required',
                'languages' => 'nullable|array',
                'languages.*.language' => 'required|string',
                'languages.*.value' => 'required|string'
            ];

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()], 422);
            }

            $setting = ChatbotSetting::withTrashed()->with('defaultTable', 'languages')->find($id);
            if ($setting) {

                $language = true;
                if ($setting->defaultTable->type == 'mensaje' || $setting->defaultTable->name == 'titulo') {
                    foreach ($data['languages'] as $langData) {
                        $existingLanguage = $setting->languages()->where('language', $langData['language'])->first();

                        if ($existingLanguage) {
                            $existingLanguage->value = $langData['value'];
                            $existingLanguage->save();
                        } else {
                            $newLanguage = new ChatbotSettingLanguage();
                            $newLanguage->chatbot_setting_id = $setting->id;
                            $newLanguage->language = $langData['language'];
                            $newLanguage->value = $langData['value'];
                            $newLanguage->save();
                        }
                    }
                }

                if ($setting->defaultTable->type == 'idioma' && $data['value'] == 0) {
                    $languages = $this->checkLanguages($setting->chatbot_id);
                    $default = $this->checkDefault($setting->chatbot_id);
                    if ($languages == false) {
                        return response()->json(['success' => false, 'message' => 'No puedes eliminar todos los lenguajes del Chatbot. Debe tener al menos 1.'], 200);
                    }
                    if ($default == $setting->defaultTable->name) {
                        return response()->json(['success' => false, 'message' => 'No puedes eliminar el lenguaje que tienes como principal'], 200);
                    }
                } else {
                    if ($setting->defaultTable->type == 'idioma') {
                        $this->addchatbotSetting($setting->defaultTable->name, $setting->chatbot_id);
                    }
                }

                if ($setting->defaultTable->name == 'modulo_agente') {
                    if ($data['value'] == 1) {
                        $module_agente = true;
                    } else {
                        $module_agente = false;
                    }
                }

                if ($data['key'] == 'logo' && $data['value'] == null) {
                    $data['value'] = '';
                }
                $setting->value = $data['value'];
                $setting->save();

                return response()->json(['success' => true, 'message' => 'Configuración actualizada correctamente.', 'data' => $setting, 'module_agent' => $module_agente], 200);
            } else {

                return response()->json(['success' => false, 'message' => 'Configuración no encontrada con el id proporcionado.'], 404);
            }
        } catch (\Throwable $th) {
            Log::error('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    public function checkLanguages($id)
    {

        $chatbotSettings = ChatbotSetting::with('defaultTable')
            ->whereHas('defaultTable', function ($query) {
                $query->where('type', 'idioma');
            })
            ->where('deleted_at', null)
            ->where('value', 1)
            ->withTrashed()
            ->where('chatbot_id', $id)
            ->get();

        if (count($chatbotSettings) > 1) {
            return true;
        } else {
            return false;
        }
    }

    public function addchatbotSetting($language, $chatbot_id)
    {
        $lastPort = ChatbotPort::max('port');
        $newPort = ($lastPort !== null) ? $lastPort + 1 : 5005;
        $existingRecord = ChatbotPort::where('chatbots_id', $chatbot_id)->where('language', $language)->exists();
        if (!$existingRecord) {
            while (ChatbotPort::where('port', $newPort)->exists()) {
                $newPort++;
            }
            $chatbotPort = new ChatbotPort;
            $chatbotPort->chatbots_id = $chatbot_id;
            $chatbotPort->port = $newPort;
            $chatbotPort->language = $language;
            $chatbotPort->save();

            Log::info('Datos enviados', ['chatbotid' => $chatbot_id, 'port' => $newPort]);
            $rasaControl = new RasaBotControl();
            $result = $rasaControl->createBot($chatbot_id, $language, $newPort);
        }
    }

    public function checkDefault($id)
    {
        $defaultLanguage = ChatbotSetting::with('defaultTable')
            ->whereHas('defaultTable', function ($query) {
                $query->where('type', 'predeterminado');
            })
            ->where('chatbot_id', $id)
            ->first();

        return $defaultLanguage->value;
    }

    public function getOneChatbotCustomerSettings(Request $request, $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'No se pudo encontrar la configuración.'], 404);
        try {
            if ($request->has('testScript') && $request->testScript == true) {
            $settings = ChatbotSetting::with('chatbot', 'defaultTable', 'languages')
                ->withTrashed()
                ->where('chatbot_id', $id)
                // ->whereHas('chatbot', function ($query) {
                //     $query->where('active', true);
                // })
                ->whereHas('defaultTable', function ($query) {
                    $query->whereIn('name', [
                        'logo',
                        'icono_bot',
                        'icono_agente',
                        'color',
                        'titulo',
                        'mensaje_reiniciar',
                        'mensaje_comenzar_nuevamente',
                        'mensaje_comenzar_nuevamente_inactividad'
                    ]);
                })
                ->get();
            } else {
                $settings = ChatbotSetting::with('chatbot', 'defaultTable', 'languages')
                ->withTrashed()
                ->where('chatbot_id', $id)
                ->whereHas('chatbot', function ($query) {
                    $query->where('active', true);
                })
                ->whereHas('defaultTable', function ($query) {
                    $query->whereIn('name', [
                        'logo',
                        'icono_bot',
                        'icono_agente',
                        'color',
                        'titulo',
                        'mensaje_reiniciar',
                        'mensaje_comenzar_nuevamente',
                        'mensaje_comenzar_nuevamente_inactividad'
                    ]);
                })
                ->get();
            }

            if ($settings->isNotEmpty()) {
                return $settings;
            } else {
                return [];
            }
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }
}
