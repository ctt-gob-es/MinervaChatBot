<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Chatbot;
use App\Models\ChatbotSetting;
use App\Models\ChatbotSettingLanguage;
use App\Models\ChatbotLog;
use App\Models\Conversation;
use App\Models\DayTimeSlot;
use App\Models\Holiday;
use App\Models\HolidayLanguage;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


class AgentController extends Controller
{
    /**
     * Inicia una conversación según los parámetros proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/startConversation",
     *     tags={"agent"},
     *     summary="Iniciar conversación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chatbot_id", "lang", "conversation_id", "token", "messages"},
     *             @OA\Property(property="chatbot_id", type="integer", example="b23jf3-mdmdso32-kso2o2", description="ID del chatbot"),
     *             @OA\Property(property="lang", type="string", description="Idioma de la conversación"),
     *             @OA\Property(property="conversation_id", type="string", description="ID de la conversación"),
     *             @OA\Property(property="token", type="string", description="Token de autenticación"),
     *             @OA\Property(property="messages", type="array", @OA\Items(type="string"), description="Lista de mensajes")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversación iniciada con éxito. El agente recibirá el siguiente JSON con el historial.",
     *         @OA\JsonContent(
     *             @OA\Property(property="chatbot_id", type="integer", example="b23jf3-mdmdso32-kso2o2", description="ID del chatbot"),
     *             @OA\Property(property="lang", type="string", description="Idioma de la conversación"),
     *             @OA\Property(property="conversation_id", type="string", description="ID de la conversación"),
     *             @OA\Property(property="token", type="string", description="Token de autenticación"),
     *             @OA\Property(
     *                 property="messages",
     *                 type="array",
     *                 description="Lista de mensajes. La propiedad messages es un array con varios mensajes.",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="message", type="string", description="Contenido del mensaje"),
     *                     @OA\Property(property="type_user", type="string", description="Tipo de usuario, bot o humano")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chatbot no encontrado o fuera de horario/festivo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="reason", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="reason", type="string"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function startConversation(Request $request)
    {
        try {
            $request->validate([
                'chatbot_id' => 'required',
                'lang' => 'required',
                'conversation_id' => 'required',
                'token' => 'required',
                'messages' => 'required|array',
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->messages();
            $errorMessage = '';

            foreach ($errors as $field => $messagesError) {
                $errorMessage .= "El campo '$field' es obligatorio. ";
            }

            return response()->json(['success' => false, 'error' => $errorMessage], 422);
        }
        $date = Carbon::now();
        $dayOfWeek = $date->isoFormat('dddd');

        $idChatbot = $request->input('chatbot_id');
        $lang = $request->input('lang');
        $idConversation = $request->input('conversation_id');
        $messages = $request->input('messages');
        $token = $request->input('token');

        $validateChatbot = Chatbot::where('id', $idChatbot)->exists();

        if (!$validateChatbot) {
            return response()->json(['success' => false, 'error' => 'El chatbot con el ID proporcionado no existe'], 404);
        }
        if ($this->isHoliday($idChatbot)) {

            $currentDate = $date->toDateString();

            $holidayFoundId = Holiday::where('chatbot_id', $idChatbot)
                                ->where('day', $currentDate)
                                ->value('id');

            $messageOut = HolidayLanguage::where('holiday_id', $holidayFoundId)->where('language', $lang)->value('message');
            if(!$messageOut){
                $messageOut = Holiday::where('chatbot_id', $idChatbot)
                                ->where('day', $currentDate)
                                ->value('description');
            }
            return response()->json(['success' => false, 'message' => $messageOut, 'reason' => 'holiday'], 404);
        }

        if ($this->isWithinTimeSlot($idChatbot)) {
            $chatbotSettId = ChatbotSetting::with('defaultTable')
            ->withTrashed()
            ->where('chatbot_id', $idChatbot)
            ->whereHas('defaultTable', function ($query) {
                $query->whereIn('name', ['mensaje_fuera_de_horario']);
            })
            ->value('id');
        $messageOut = ChatbotSettingLanguage::where('chatbot_setting_id', $chatbotSettId)->where('language', $lang)->value('value');
        if(!$messageOut){
            $messageOut = ChatbotSetting::with('defaultTable')
                ->withTrashed()
                ->where('chatbot_id', $idChatbot)
                ->whereHas('defaultTable', function($query){
                    $query->whereIn('name', ['mensaje_fuera_de_horario']);
                })
                ->value('value');
        }
        return response()->json(['success' => false, 'message' => $messageOut, 'reason' => 'time_slot'], 404);
        }

        $client = new Client();

        try {
            $endpointChatbot = ChatbotSetting::with('defaultTable')
                ->withTrashed()
                ->where('chatbot_id', $idChatbot)
                ->whereHas('defaultTable', function ($query) {
                    $query->whereIn('name', ['ruta_agente']);
                })
                ->value('value');
            if (empty($endpointChatbot)) {
                return response()->json(['success' => false, 'error' => 'No tienes configurada la URL del chatbot '.$idChatbot.' para gestión de agentes'], 404);
            }

            $response = $client->request('POST', $endpointChatbot, [
                'json' => [
                    'lang' => $lang,
                    'chatbot_id' => $idChatbot,
                    'conversation_id' => $idConversation,
                    'messages' => $messages,
                    'token' => $token
                ]
            ]);

            $chatbotSettIdTrue = ChatbotSetting::with('defaultTable')
            ->withTrashed()
            ->where('chatbot_id', $idChatbot)
            ->whereHas('defaultTable', function ($query) {
                $query->whereIn('name', ['mensaje_solicitar_agente']);
            })
            ->value('id');
            $messageOutTrue = ChatbotSettingLanguage::where('chatbot_setting_id', $chatbotSettIdTrue)->where('language', $lang)->value('value');
            if(!$messageOutTrue){
                $messageOutTrue = ChatbotSetting::with('defaultTable')
                ->withTrashed()
                ->where('chatbot_id', $idChatbot)
                ->whereHas('defaultTable', function($query){
                    $query->whereIn('name', ['mensaje_solicitar_agente']);
                })
                ->value('value');
            }
            return response()->json(['success'=> true, 'message'=>$messageOutTrue], 200);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $reasonPhrase = $response->getReasonPhrase();
                return response()->json(['success'=>false,'reason'=>'URL','error' => 'Error al enviar la conversación: '.$endpointChatbot . $reasonPhrase], $statusCode);
            } else {
                return response()->json(['success'=>false,'reason'=>'URL','error' => 'Error al enviar la conversación: No se pudo establecer conexión con el servidor. '.$endpointChatbot], 500);
            }
            return response()->json(['success'=>false, 'reason' => 'URL', 'error'=> 'Tu configuración ruta_agente es erronea. No es posible conectarse o no existe.']);
        }
    }

    /**
     * Cierra una conversación según los parámetros proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/closeConversation",
     *     tags={"agent"},
     *     summary="Cerrar conversación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"conversation_id", "chatbot_id", "lang"},
     *             @OA\Property(property="conversation_id", type="integer", description="ID de la conversación"),
     *             @OA\Property(property="chatbot_id", type="integer", example="b23jf3-mdmdso32-kso2o2", description="ID del chatbot"),
     *             @OA\Property(property="lang", type="string", enum={"castellano", "ingles", "valenciano"}, description="Idioma de la conversación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversación cerrada con éxito",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *    @OA\Response(
     *         response=404,
     *         description="Error en los datos | La conversación está cerrada | La conversación está siendo tratada por el bot | El idioma no es correcto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación | Los campos required son obligatorios",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function closeConversation(Request $request)
    {
        $basePath = config('app.host');
        $rules = [
            'conversation_id' => 'required|integer',
            'chatbot_id' => 'required',
            'lang' => 'required|in:castellano,ingles,valenciano',
        ];

        $messages = [
            'conversation_id.required' => 'El dato "conversation_id" es obligatorio.',
            'conversation_id.integer' => 'El dato "conversation_id" debe ser un número entero.',
            'lang.required' => 'El dato "lang" es obligatorio.',
            'lang.in' => 'El dato "lang" debe ser castellano, ingles o valenciano.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->all(),
            ], 400);
        }

        $conversationId = $request->input('conversation_id');
        $chatbot_id = $request->input('chatbot_id');
        $language = $request->input('lang');

        $validateChatbot = Chatbot::where('id', $chatbot_id)->exists();

        if (!$validateChatbot) {
            return response()->json(['success' => false, 'error' => 'El chatbot con el ID proporcionado no existe'], 404);
        }

        $conversationExist = Conversation::where('id', $conversationId)->first();

        if(!$conversationExist){
            return response()->json([
                'success' => false,
                'error' => 'La conversación que buscas no existe.'
            ]);
        } else {
            if($conversationExist->finished == 1){
                return response()->json([
                    'success' => false,
                    'error' => 'La conversación ya está cerrada.'
                ]);
            } elseif ($conversationExist->agent == 0){
                return response()->json([
                    'success' => false,
                    'error' => 'La conversación que buscas está siendo tratada por el bot.'
                ]);
            } elseif ($conversationExist->language != $language){
                return response()->json([
                    'success' => false,
                    'error' => 'El idioma en que quieres consultar la conversación no es el correcto'
                ]);
            }

        }

        $desvioAgente = false;

        $client = new Client();

        try{
            $client->request('POST', $basePath.'/api/conversation', [

                'json' => [
                    'conversation_id' => $conversationId,
                    'chatbot_id' => $chatbot_id,
                    'desvioAgente' => $desvioAgente,
                    'lang' => $language,
                    'type_user' => 'agente'
                ]
            ]);

            return response()->json(['success' => true, 'message' => 'La conversación se cerró con exito']);
        } catch (Exception $e) {
            Log::error('Error sending request to conversation endpoint: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'La conversación no se pudo cerrar, no hemos podido conectar con el servidor'], 200);
        }
    }

    /**
     * Envía un mensaje a un agente según los parámetros proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/sendToAgent",
     *     tags={"agent"},
     *     summary="Enviar mensaje a agente",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chatbot_id", "lang", "conversation_id", "token", "messages"},
     *             @OA\Property(property="chatbot_id", type="integer", description="ID del chatbot"),
     *             @OA\Property(property="lang", type="string", description="Idioma del mensaje"),
     *             @OA\Property(property="conversation_id", type="string", description="ID de la conversación"),
     *             @OA\Property(property="token", type="string", description="Token de autenticación"),
     *             @OA\Property(property="messages", type="string", description="Mensajes a enviar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje enviado correctamente. El agente recibirá el siguiente JSON.",
     *         @OA\JsonContent(
     *             @OA\Property(property="chatbot_id", type="integer", example="b23jf3-mdmdso32-kso2o2", description="ID del chatbot"),
     *             @OA\Property(property="lang", type="string", description="Idioma de la conversación"),
     *             @OA\Property(property="conversation_id", type="string", description="ID de la conversación"),
     *             @OA\Property(property="token", type="string", description="Token de autenticación"),
     *             @OA\Property(
     *                 property="messages",
     *                 type="object",
     *                 description="Mensaje individual. Propiedad messages de tipo objeto",
     *                     @OA\Property(property="message", type="string", description="Contenido del mensaje"),
     *                     @OA\Property(property="type_user", type="string", description="Tipo de usuario, bot o humano")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="URL de agente no configurada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function toAgent(Request $request)
    {
        try {
            $request->validate([
                'chatbot_id' => 'required',
                'lang' => 'required',
                'conversation_id' => 'required',
                'token' => 'required',
                'messages' => 'required',
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->messages();
            $errorMessage = '';

            foreach ($errors as $field => $messagesError) {
                $errorMessage .= "El campo '$field' es obligatorio. ";
            }

            return response()->json(['success' => false, 'error' => $errorMessage], 422);
        }

        $lang = $request->input('lang');
        $idConversation = $request->input('conversation_id');
        $idChatbot = $request->input('chatbot_id');
        $messages = $request->input('messages');
        $token = $request->input('token');

        $client = new Client();

        try {
            $endpointChatbot = ChatbotSetting::with('defaultTable')
                ->withTrashed()
                ->where('chatbot_id', $idChatbot)
                ->whereHas('defaultTable', function ($query) {
                    $query->whereIn('name', ['ruta_agente']);
                })
                ->value('value');

            if (empty($endpointChatbot)) {
                return response()->json(['success'=> false, 'error' => 'No tienes configurada la URL del chatbot '.$idChatbot.' para gestión de agentes', 'reason' => 'ruta'], 404);
            }

            $response = $client->post($endpointChatbot, [
                'json' => [
                    'lang' => $lang,
                    'chatbot_id' => $idChatbot,
                    'conversation_id' => $idConversation,
                    'messages' => $messages,
                    'token' => $token
                ]
            ]);
            return response()->json(['success'=> true, 'message' => 'Mensaje enviado correctamente'], 200);
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $reasonPhrase = $response->getReasonPhrase();
                return response()->json(['success'=>false, 'error' => 'Error al enviar el mensaje: ' . $reasonPhrase], $statusCode);
            } else {
                return response()->json(['success'=>false, 'error' => 'Error al enviar el mensaje: No se pudo establecer conexión con el servidor'], 500);
            }
        }

    }
    /**
     * Envía un mensaje desde un agente según los parámetros proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/getFromAgent",
     *     tags={"agent"},
     *     summary="Enviar mensaje desde agente",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"conversation_id", "message", "chatbot_id", "lang"},
     *             @OA\Property(property="conversation_id", type="integer", description="ID de la conversación"),
     *             @OA\Property(property="message", type="string", description="Mensaje a enviar"),
     *             @OA\Property(property="chatbot_id", type="string", example="b23jf3-mdmdso32-kso2o2", description="ID del chatbot"),
     *             @OA\Property(property="lang", type="string", description="Idioma del mensaje", enum={"castellano", "ingles", "valenciano"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mensaje enviado correctamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error en los datos | La conversación está cerrada | La conversación está siendo tratada por el bot | El idioma no es correcto",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación | Los campos required son obligatorios",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="error", type="string"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function fromAgent(Request $request)
    {
        $basePath = config('app.host');
        $rules = [
            'conversation_id' => 'required|integer',
            'message' => 'required|string',
            'chatbot_id' => 'required',
            'lang' => 'required|in:castellano,ingles,valenciano',
        ];

        $messages = [
            'conversation_id.required' => 'El dato "conversation_id" es obligatorio.',
            'conversation_id.integer' => 'El dato "conversation_id" debe ser un número entero.',
            'message.required' => 'El dato "message" es obligatorio.',
            'message.string' => 'El dato "message" debe ser una cadena de texto.',
            'chatbot_id.required' => 'El dato "chatbot_id" es obligatorio.',
            'lang.required' => 'El dato "lang" es obligatorio.',
            'lang.in' => 'El dato "lang" debe ser castellano, ingles o valenciano.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 400);
        }

        $conversationId = $request->input('conversation_id');
        $message = $request->input('message');
        $chatbot_id = $request->input('chatbot_id');
        $language = $request->input('lang');
        $desvioAgente = true;

        $validateChatbot = Chatbot::where('id', $chatbot_id)->exists();

        if (!$validateChatbot) {
            return response()->json(['success' => false, 'error' => 'El chatbot con el ID proporcionado no existe'], 404);
        }

        $conversationExist = Conversation::where('id', $conversationId)->first();

        if(!$conversationExist){
            return response()->json([
                'success' => false,
                'error' => 'La conversación que buscas no existe.'
            ]);
        } else {
            if($conversationExist->finished == 1){
                return response()->json([
                    'success' => false,
                    'error' => 'La conversación ya está cerrada.'
                ]);
            } elseif ($conversationExist->agent == 0){
                return response()->json([
                    'success' => false,
                    'error' => 'La conversación que buscas está siendo tratada por el bot.'
                ]);
            } elseif ($conversationExist->language != $language){
                return response()->json([
                    'success' => false,
                    'error' => 'El idioma en que quieres consultar la conversación no es el correcto'
                ]);
            }

        }

        $client = new Client();
        try{
            $client->request('POST', $basePath.'/api/conversation', [
                'json' => [
                    'conversation_id' => $conversationId,
                    'chatbot_id' => $chatbot_id,
                    'message' => $message,
                    'desvioAgente' => $desvioAgente,
                    'lang' => $language,
                    'type_user' => 'agente'
                ]
            ]);
            return response()->json(['success' => true, 'message' => 'Tu mensaje ha llegado con exito']);
        } catch (Exception $e) {
            Log::error('Error sending request to conversation endpoint: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Tu mensaje no se pudo enviar, no hemos podido conectar con el servidor'], 200);
        }
    }

    private function isHoliday($idChatbot)
    {
        $currentDate = Carbon::now()->toDateString();

        $holidayFound = Holiday::where('chatbot_id', $idChatbot)
                                ->where('day', $currentDate)
                                ->exists();

        return $holidayFound;
    }

    private function isWithinTimeSlot($idChatbot)
    {
        $date = Carbon::now();
        $currentTime = $date->format('H:i:s');
        $dayOfWeek = $date->isoFormat('dddd');
        $currentTimeWithinSlot = false;

        $foundChatbot = Chatbot::where('id', $idChatbot)
                                ->with(['schedule' => function ($query) use ($dayOfWeek) {
                                    $query->whereHas('schedule', function ($q) {
                                        $q->where('active', 1);
                                    })->whereHas('dayTimeSlot.day', function ($q) use ($dayOfWeek) {
                                        $q->where('day', $dayOfWeek);
                                    });
                                }])
                                ->first();
        if ($foundChatbot->schedule->isEmpty()) {
            $currentTimeWithinSlot = true;
        } else {
            $idDayTimeSlots = $foundChatbot->schedule->pluck('id_day_time_slot');
            $timeSlots = DayTimeSlot::whereIn('id', $idDayTimeSlots)->get();

            foreach ($timeSlots as $timeSlot) {
                if ($currentTime >= $timeSlot->start_time && $currentTime <= $timeSlot->end_time) {
                    $currentTimeWithinSlot = true;
                    break;
                }
            }
        }

        if (!$currentTimeWithinSlot) {
            return true;
        } else {
            return false;
        }
    }

}
