<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\ChatbotLog;
use App\Models\Node;
use App\Models\ConversationStatus;
use App\Models\Conversation;
use App\Models\ResCombination;
use App\Models\Concept;
use App\Models\Intentions;
use Illuminate\Http\Request;
use App\Models\ChatbotSetting;
use App\Models\ChatbotRasa;
use App\Models\Answers;
use App\Models\ConceptError;
use App\Models\NodeTransition;
use App\Models\ConversationLog;
use App\Models\ConversationIntention;
use Illuminate\Validation\Rule;
use App\Events\EventConversation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Client;

use Aunnait\Rasalicante\RasaComm;

class ConversationController extends Controller
{
    public function index()
    {
        $query = Conversation::join('conversation_logs', 'conversation_logs.conversation_id', 'conversations.id')
            ->join('chatbot_logs', 'chatbot_logs.id', 'conversations.chatbot_log_id')
            ->join('conversation_status', 'conversation_status.id', 'conversations.conversation_status_id')
            ->where('chatbot_logs.chatbot_id', $_GET['chatbot_id'])
            ->select(
                'conversations.id',
                'conversations.conversation_status_id',
                'conversations.finished',
                'conversations.agent',
                'conversations.chatbot_log_id',
                'conversations.created_at',
                'conversations.updated_at',
                'conversation_status.name as state',
                DB::raw('(SELECT MIN(created_at) FROM conversation_logs WHERE conversation_id = conversations.id) as message_init_date'),
                DB::raw('(SELECT MAX(created_at) FROM conversation_logs WHERE conversation_id = conversations.id) as message_finish_date'),
                DB::raw('TIME_FORMAT(TIMEDIFF((SELECT MAX(created_at) FROM conversation_logs WHERE conversation_id = conversations.id), (SELECT MIN(created_at) FROM conversation_logs WHERE conversation_id = conversations.id)), "%H:%i:%s") as time_conversation'),
                DB::raw('(SELECT COUNT(*) FROM conversation_logs WHERE conversation_id = conversations.id) as total_message')
            )
            ->groupBy('conversations.id', 'conversations.conversation_status_id', 'conversations.finished', 'conversations.agent', 'conversations.chatbot_log_id', 'conversations.created_at', 'conversations.updated_at', 'conversation_status.name');

        if ($_GET['from'] !== 'null' && $_GET['to'] !== 'null') {
            $to = date('Y-m-d', strtotime($_GET['to'] . ' +1 day'));
            $query->whereBetween('conversations.created_at', [$_GET['from'], $to]);
        }

        if ($_GET['state'] !== 'null') {
            $query->where('conversations.conversation_status_id', $_GET['state']);
        }

        $data = $query->orderByDesc('conversations.created_at')->get();

        return response()->json($data);
    }

    /**
     * Muestra los detalles de una conversación.
     *
     * Este método devuelve los detalles de una conversación específica identificada por su ID.
     * Si se proporciona un ID válido, devuelve los detalles de la conversación en formato JSON.
     * Si no se puede encontrar la conversación con el ID proporcionado, se devuelve un error 404.
     * Si ocurre un error interno del servidor, se devuelve un error 500.
     *
     * @param  string  $id  El ID de la conversación
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/api/getConversationDetailApi/{id}",
     *     tags={"conversation"},
     *     summary="Mostrar detalles de una conversación",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la conversación"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la conversación obtenidos exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="conversation_id", type="number", example="2"),
     *                 @OA\Property(property="message", type="string", example="Hola, ¿cómo estás?"),
     *                 @OA\Property(property="type_user", type="string", example="agente"),
     *                 @OA\Property(property="node_id", type="number", example="1")
     *              )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Conversación no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="La conversación especificada no fue encontrada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Ocurrió un error interno en el servidor.")
     *         )
     *     )
     * )
     */
    public function show(string $id)
    {
        try {
            $conversationExists = Conversation::where('id', $id)->exists();

            if (!$conversationExists) {
                return response()->json(['error' => 'La conversación especificada no fue encontrada.'], 404);
            }

            $detail = ConversationLog::where('conversation_id', $id)->get();
            return response()->json($detail);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }


    /**
     * Obtiene los estados de conversación.
     *
     * Este método devuelve una lista de los estados de conversación disponibles.
     * Si los estados de conversación están disponibles, devuelve la lista de estados en formato JSON.
     * Si no se pueden obtener los estados de conversación, se devuelve un error.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/api/getConversationStatusApi",
     *     tags={"conversation"},
     *     summary="Obtener estados de conversación",
     *     @OA\Response(
     *         response=200,
     *         description="Estados de conversación obtenidos exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="En Curso")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Ocurrió un error interno en el servidor.")
     *         )
     *     )
     * )
     */
    public function getConversationStatus()
    {
        try {
            $data = ConversationStatus::get();
            return response()->json($data);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    /**
     * Procesa una conversación.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/conversation",
     *     tags={"conversation"},
     *     summary="Procesar una conversación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"conversation_id", "chatbot_id"},
     *             @OA\Property(property="conversation_id", type="integer", example="1"),
     *             @OA\Property(
     *                 property="chatbot_id",
     *                 type="string",
     *                 example="123e4567-e89b-12d3-a456-426614174000",
     *                 description="Debe ser un UUID válido de un chatbot existente y activo."
     *             ),
     *             @OA\Property(property="message", type="string", example="Hola, ¿cómo estás?"),
     *             @OA\Property(property="selection", type="string", example="opcion1"),
     *             @OA\Property(property="type_user", type="string", enum={"ciudadano", "agente", "bot"}, example="bot"),
     *             @OA\Property(property="node", type="string", example="nodo1"),
     *             @OA\Property(property="lang", type="string", example="es"),
     *             @OA\Property(property="type_faq", type="string", example="pregunta"),
     *             @OA\Property(property="desvioAgente", type="string", nullable=true, example="desviacion")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversación procesada correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="mensaje", type="string", example="Conversación procesada correctamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Errores de validación.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="object", example={"conversation_id": {"El campo conversation_id es obligatorio"}})
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El chatbot_id no es válido.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="El chatbot_id no es válido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Ha ocurrido un error"),
     *             @OA\Property(property="line", type="integer", example=42)
     *         )
     *     )
     * )
     */
    public function conversation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => 'required',
            'chatbot_id' => [
                Rule::exists('chatbots', 'id')->where(function ($query) {
                    $query->where('id', 'REGEXP', '[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}');
                }),
            ],
            'type_user' => ['nullable', Rule::in(['ciudadano', 'agente', 'bot'])],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $type_user = $request->input('type_user', 'bot');

        try {
            DB::beginTransaction();

            $conversation_id = $request->input('conversation_id');
            $message = $request->input('message');
            $selection = $request->input('selection');
            $chatbot_id = $request->input('chatbot_id');
            $type_user = $request->input('type_user');
            $node = $request->input('node');
            $language = $request->input('lang');
            $typeFAQ = $request->input('type_faq');
            $desvioAgente = $request->input('desvioAgente', null);
            $testScript = $request->input('testScript', null);

            // Verificar existencia de chatbot
            if(isset($testScript) && $testScript == true){
                $chatbot = Chatbot::where('id', $chatbot_id)->exists();
                if (!$chatbot) {
                    return response()->json(['error' => 'El chatbot_id no es válido'], 404);
                }
            } else {
                $chatbot = Chatbot::where('id', $chatbot_id)->where('active', 1)->exists();
                if (!$chatbot) {
                    return response()->json(['error' => 'El chatbot_id no es válido'], 404);
                }
            }

            // Procesar conversación
            $this->processConversation($conversation_id, $node, $message, $selection, $type_user, $chatbot_id, $language, $typeFAQ, $desvioAgente);

            DB::commit();

            return response()->json(['mensaje' => 'Conversación procesada correctamente'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage(), 'line' => $e->getLine()], 500);
        }
    }

    // Procesar conversación
    private function processConversation($conversation_id, $node, $message, $selection, $type_user, $chatbot_id, $language, $typeFAQ, $desvioAgente)
    {
        if ($desvioAgente !== null && $desvioAgente === true) {
            $this->processAgentConversation($conversation_id, $message, $chatbot_id, $type_user, $language);
            return;
        } elseif ($desvioAgente !== null && $desvioAgente === false) {
            $closeConversation['conversation_id'] = $conversation_id;
            $closeConversation['message'] = '';
            $closeConversation['type_user'] = "bot";
            $closeConversation['node'] = null;
            $closeConversation['type'] = 'text';
            $closeConversation['idNode'] = null;
            $closeConversation['chatbot_id'] = $chatbot_id;
            $closeConversation['end'] = '1';

            $this->CreateConversationLog($closeConversation, $language, true);

            $chatbotsRasa = ChatbotRasa::where('chatbot_id', $chatbot_id)
                            ->where('user', $conversation_id)
                            ->where('status', 1)
                            ->whereNotNull('intention')
                            ->get();
            foreach ($chatbotsRasa as $chatbotRasa) {
                $chatbotRasa->status = 0;
                $chatbotRasa->save();
            }
            return;

        }

        if ($node !== null) {
            // Procesar conversación ya iniciada
            $this->processExistingConversation($conversation_id, $node, $message, $selection, $type_user, $chatbot_id, $language, $typeFAQ);
        } else {
            // Procesar nueva conversación
            $this->processNewConversation($conversation_id, $node, $message, $selection, $type_user, $chatbot_id, $language, $typeFAQ);
        }
    }

    // Lógica para procesar conversación ya iniciada
    private function processExistingConversation($conversation_id, $node, $message, $selection, $type_user, $chatbot_id, $language, $typeFAQ)
    {
        if (is_null($message) && is_null($selection)) {
            return response()->json(['error' => 'message o selection son requeridos'], 404);
        }

        // Conversación ya iniciada
        $conversation = Conversation::where('id', $conversation_id)->first();
        $idNode = $this->getIdNode($chatbot_id, $node);
        if ($conversation->agent == 1) {
            $this->toAgent($chatbot_id, $language, $conversation_id, $message);

            $conversationLog = new ConversationLog();
            $conversationLog->conversation_id = $conversation_id;
            $conversationLog->message = $message;
            $conversationLog->type_user = $type_user;
            $conversationLog->node_id = null;
            $conversationLog->save();
            return;
        }
        if (!$idNode) {
            throw new \ErrorException('No se encontro la node');
            return response()->json(['error' => 'No se encontro la node'], 500);
        }

        //se valida que la conversación exista
        if (!$conversation) {
            throw new \ErrorException('No se encontro la conversación');
            return response()->json(['error' => 'No se encontro la conversación'], 500);
        }

        $messageCustomer['conversation_id'] = $conversation_id;
        $messageCustomer['message'] = isset($message) ? $message : $selection;
        $messageCustomer['type_user'] = $type_user;
        $messageCustomer['type'] = 'text';
        $messageCustomer['idNode'] = $idNode->id;
        $messageCustomer['chatbot_id'] = $chatbot_id;

        if ($language != null) {
            $this->updateConversationLanguage($conversation_id, $language);
        }

        //ACTUALIZAR validate_response
        ///VALIDAR CANTIDAD DE NEGATIVA=1 EN conversation_intentions SI ES MAYOR A 2 ENVIARLO A AGENTE.
        $calificacionPositivaNegativa = false;

        // Obtener los registros ordenados por id
        $conversationIntentions = ConversationIntention::where('conversation_id', $conversation_id)
            ->where('type', 'abierta')
            ->orderBy('id')
            ->get();
        $consecutiveCount = 0;
        // Recorrer los registros
        foreach ($conversationIntentions as $intention) {
            if ($intention->training_status_id == 1) {
                $consecutiveCount++;
                if ($consecutiveCount > 2) {
                    $calificacionPositivaNegativa = true;
                    break;
                }
            } else {
                $consecutiveCount = 0;
            }
        }
        $chatbotRasaValidateResponse = ChatbotRasa::where('chatbot_id', $chatbot_id)
        ->where('user', $conversation_id)
        ->where('validate_response', 'validate_response')
        ->where('status', 1)
        ->exists();

        $conversationIntentionValidateResponse = ConversationIntention::where('conversation_id', $conversation_id)
        ->whereIn('training_status_id', [2,4])
        ->where('type', 'abierta')
        ->exists();

        if ($message == "Si") {
            $training_status_id = 3;
        } elseif ($message == "No") {
            $training_status_id = 1;
        }else{
            $training_status_id = 4;
        }

        if($chatbotRasaValidateResponse && $conversationIntentionValidateResponse){
            $this->updateConversationIntention($chatbot_id, $conversation_id, $training_status_id);
            $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
            ->where('user', $conversation_id)
            ->where('status', 1)
            ->get();
            foreach ($chatbotsRasaUp as $chatbotRasa) {
                ChatbotRasa::where('id', $chatbotRasa->id)->update([
                    'status' => 0
                ]);
            }
        }else{
            $chatbotRasaValidTerminada = ChatbotRasa::where('chatbot_id', $chatbot_id)
            ->where('user', $conversation_id)
            ->where('validate_response', 'validate_response')
            ->where('intention', 'FORMULARIO_TERMINADO')
            ->where('status', 0)
            ->exists();

            $intencionesSimple = ChatbotRasa::where('chatbot_id', $chatbot_id)
            ->where('user', $conversation_id)
            ->where('validate_response', 'validate_response')
            ->whereNull('form')
            ->where('status', 0)
            ->exists();

            $conversationIntentionValidTerminada = ConversationIntention::where('conversation_id', $conversation_id)
            ->whereIn('training_status_id', [2,4])
            ->where('type', 'abierta')
            ->exists();

            if($chatbotRasaValidTerminada && $conversationIntentionValidTerminada){
                $this->updateConversationIntention($chatbot_id, $conversation_id, $training_status_id);
            }elseif($intencionesSimple && $conversationIntentionValidTerminada){
                $this->updateConversationIntention($chatbot_id, $conversation_id, $training_status_id);
            }
        }

        $logResultCustomer = $this->CreateConversationLog($messageCustomer, $language, false);
        if ($logResultCustomer === null) {
            throw new \ErrorException('No se pudo crear el registro de ConversationLog');
            return response()->json(['error' => 'No se pudo crear el registro de ConversationLog'], 500);
        }

        //enviar respuesta de la intención
        $no_follow_flow = false;
        $textoAnswerIntention=null;
        if (isset($typeFAQ) && $typeFAQ !== null) {
            if ($typeFAQ['type'] === "free_question" && isset($typeFAQ['question'])) {
                $translations = $this->getTranslations();
                $language = $this->getLanguage($chatbot_id, $language);

                try {
                    $rasaComm = new RasaComm();
                    Log::info('Datos enviados rasaComm->enviarMensaje ', ['idBot' => $chatbot_id, 'language' => $language, 'usuario' => $conversation_id, 'mensaje' => $typeFAQ['question']]);
                    $result = $rasaComm->enviarMensaje($chatbot_id, $language, $conversation_id, $typeFAQ['question']);
                    Log::info('Datos recibidos rasaComm->enviarMensaje ', ['result' => $result]);
                    $result = json_decode($result, true);
                } catch (\Exception $e) {
                    $messageRasa['type_user'] = 'bot';
                    $messageRasa['type'] = 'text';
                    $messageRasa['idNode'] = $idNode->id;
                    $messageRasa['chatbot_id'] = $chatbot_id;
                    $messageRasa['conversation_id'] = $conversation_id;
                    $messageRasa['message'] = $translations['error'][$language];
                    $messageRasa['end'] = '1';
                    $this->CreateConversationLog($messageRasa, $language, true);
                    Log::info('ERROR Datos enviados rasaComm->enviarMensaje', ['error sendmessage' => $e]);
                    return;
                }

                if (isset($result) && isset($result['texto'])) {
                    $chatbotRasa = new ChatbotRasa();

                    $chatbotRasa->chatbot_id = $chatbot_id;
                    $chatbotRasa->validate_response = null;
                    $chatbotRasa->text = $result['texto'];
                    $chatbotRasa->intention = $result['intencion'] ?? null;
                    if (isset($result['slots']) && is_array($result['slots'])) {
                        $chatbotRasa->slots = json_encode($result['slots']);
                    } else {
                        $chatbotRasa->slots = $result['slots'] ?? null;
                    }
                    $chatbotRasa->user = $result['usuario'];
                    $chatbotRasa->form = $result['form'] ?? null;
                    $chatbotRasa->save();

                    if(!($result['intencion'] === 'mood_great' || $result['intencion'] === "bot_challenge" || $result['intencion'] === "greet" || $result['intencion'] === "goodbye" || $result['intencion'] === "affirm" || $result['intencion'] === 'nlu_fallback' || $result['intencion'] === 'desvio_agente' || $result['intencion'] === 'cancelar' || $result['intencion'] === 'FORMULARIO_TERMINADO' || $result['intencion'] === "deny" || $result['intencion'] === "mood_unhappy")){

                        $question_citizen = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->whereNotNull('question_citizen')
                        ->where('status', 1)
                        ->exists();
                        $ChatbotRasaqQuestionCitizen = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->where('status', 1)
                        ->latest('created_at')
                        ->first();

                        $intentionFormularioTerminado = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->where("intention",'FORMULARIO_TERMINADO')
                        ->count();

                        if (!$question_citizen && $ChatbotRasaqQuestionCitizen) {
                            $ChatbotRasaqQuestionCitizen->question_citizen = $typeFAQ['question'];
                            $ChatbotRasaqQuestionCitizen->save();
                        }elseif($intentionFormularioTerminado >= 1 && $ChatbotRasaqQuestionCitizen){
                            $ChatbotRasaqQuestionCitizen->question_citizen = $typeFAQ['question'];
                            $ChatbotRasaqQuestionCitizen->save();
                        }
                    }
                    if($result['intencion'] === 'FORMULARIO_TERMINADO' && isset($result['form_terminado']) && !empty($result['form_terminado'])){
                        $chatbotRasaqQuestionCitizen = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->whereNull('question_citizen')
                        ->where('status', 1)
                        ->first();

                        if (!$chatbotRasaqQuestionCitizen) {
                            $chatbotRasa->question_citizen = $typeFAQ['question'];
                            $chatbotRasa->save();
                        }

                        $messageCount = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->count();

                        if ($messageCount == 1) {
                            $chatbotRasa->question_citizen = $typeFAQ['question'];
                            $chatbotRasa->save();
                        }
                    }


                    if($result['intencion'] === 'desvio_agente'){
                        $chatbotRasaqQuestionCitizen = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->where('intention','desvio_agente')
                        ->where('status', 1)
                        ->first();
                        if ($chatbotRasaqQuestionCitizen) {
                            $chatbotRasa->question_citizen = $typeFAQ['question'];
                            $chatbotRasa->save();
                        }
                    }

                    //validar falta de entrenamiento
                    if (isset($result['intencion'])) {

                        $textRasa = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $result['usuario'])
                        ->where('status', 1)
                        ->whereNotNull('intention')
                        ->where('intention', '!=', '')
                        ->first();

                        if ($textRasa) {
                            $intentionChatbotRasa = $textRasa->intention;
                            $intentionId = Intentions::where('chatbot_id', $chatbot_id)
                                ->where('name', $intentionChatbotRasa)
                                ->value('id');
                        }
                        if ($result['intencion'] === 'mood_great' || $result['intencion'] === "bot_challenge" || $result['intencion'] === "greet" || $result['intencion'] === "goodbye" || $result['intencion'] === "affirm" || $result['intencion'] === "deny" || $result['intencion'] === "mood_unhappy") {
                            $messageRasa['type_user'] = 'bot';
                            $messageRasa['type'] = 'text';
                            $messageRasa['idNode'] = $idNode->id;
                            $messageRasa['chatbot_id'] = $chatbot_id;
                            $messageRasa['conversation_id'] = $conversation_id;
                            $messageRasa['message'] = $translations['no_he_entendido'][$language];

                            $this->CreateConversationLog($messageRasa, $language, true);
                            return;
                        } elseif ($result['intencion'] === 'nlu_fallback') {
                            //'estoy_en'
                            $answerConceptError=null;
                            if(isset($result['requested_slot']) && $result['requested_slot'] !== null && $result['requested_slot'] !== ""){
                                $answerConceptError = ConceptError::whereHas('concept', function($query) use ($chatbot_id, $result) {
                                    $query->where('chatbot_id', $chatbot_id)
                                          ->where('name', $result['requested_slot']);
                                })
                                ->where('language', $language)
                                ->select('answer')
                                ->get();

                                if(!$answerConceptError){
                                    $answerConceptError=null;
                                }
                            }

                            // Obtener los registros consecutivos de nlu_fallback
                            $ChatbotRasaNluFallbackConsecutive = false;
                            $ChatbotRasaNluFallbackConsecutive = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                ->where('user', $result['usuario'])
                                ->whereNotNull('intention')
                                ->where('intention', '!=', '')
                                ->where('status', 1)
                                ->orderBy('id')
                                ->get();

                            $consecutiveCount = 0;
                            // Recorrer los registros
                            foreach ($ChatbotRasaNluFallbackConsecutive as $consecutive) {
                                if ($consecutive->intention == 'nlu_fallback' && $consecutive->status == 1 ) {
                                    $consecutiveCount++;
                                    if ($consecutiveCount > 2) {
                                        $ChatbotRasaNluFallbackConsecutive = true;
                                        break;
                                    }
                                } else {
                                    $consecutiveCount = 0;
                                    $ChatbotRasaNluFallbackConsecutive = false;
                                }
                            }

                            // Agregar la lógica condicional basada en el conteo nlu_fallback
                            if ($ChatbotRasaNluFallbackConsecutive) {
                                if (isset($result['form']) && $result['form'] !== null) {
                                    $chatbot = ChatbotSetting::with('defaultTable')
                                        ->withTrashed()
                                        ->where('chatbot_id', $chatbot_id)
                                        ->whereHas('defaultTable', function ($query) {
                                            $query->whereIn('name', ['modulo_agente']);
                                        })
                                        ->value('value');
                                    if ($chatbot === "1") {
                                        $desvioAgente = $this->desvioAgente($chatbot_id, $language, $result['usuario']);
                                        if ($desvioAgente['success']) {
                                            if (isset($desvioAgente['message'])) {
                                                $no_follow_flow = true;
                                                $messageRasa['type_user'] = 'bot';
                                                $messageRasa['type'] = 'text';
                                                $messageRasa['idNode'] = null;
                                                $messageRasa['chatbot_id'] = $chatbot_id;
                                                $messageRasa['conversation_id'] = $conversation_id;
                                                $messageRasa['message'] = $desvioAgente['message'];
                                                $messageRasa['faq'] = 'faq';
                                                $messageRasa['dataFAQ'][] = ['text' => $desvioAgente['message'], 'action' => 'free_question'];

                                                $this->CreateConversationLog($messageRasa, $language, true);
                                            }
                                        } else {
                                            if (isset($desvioAgente['message'])) {
                                                $messageRasa['type_user'] = 'bot';
                                                $messageRasa['type'] = 'text';
                                                $messageRasa['idNode'] = $idNode->id;
                                                $messageRasa['chatbot_id'] = $chatbot_id;
                                                $messageRasa['conversation_id'] = $conversation_id;
                                                $messageRasa['message'] = $desvioAgente['message'];
                                                $messageRasa['end'] = '1';
                                                $this->CreateConversationLog($messageRasa, $language, true);

                                                //cuando hay mas de 3 nlu_fallback y el modulo de agente esta activo
                                                // y cuando esta fuera de la franja horaria cerramos conversación.
                                                $textRasa = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                                ->where('user', $result['usuario'])
                                                ->where('status', 1)
                                                ->whereNotNull('intention')
                                                ->where('intention', '!=', '')
                                                ->first();

                                                $this->updateOrCreateConversationIntention($conversation_id, null, $textRasa->question_citizen, $result['texto'], "abierta",4);

                                                $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                                ->where('user', $conversation_id)
                                                ->where('status', 1)
                                                ->get();
                                                foreach ($chatbotsRasaUp as $chatbotRasa) {
                                                    ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                                        'status' => 0
                                                    ]);
                                                }
                                            } else {
                                                $messageRasa['type_user'] = 'bot';
                                                $messageRasa['type'] = 'text';
                                                $messageRasa['idNode'] = $idNode->id;
                                                $messageRasa['chatbot_id'] = $chatbot_id;
                                                $messageRasa['conversation_id'] = $conversation_id;
                                                if($answerConceptError !==null){
                                                    $messageRasa['message'] = $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                                    ? $answerConceptError[0]->answer
                                                    : $translations['no_he_entendido'][$language];
                                                }else{
                                                    $messageRasa['message'] = $translations['no_he_entendido'][$language];
                                                }

                                                $messageRasa['end'] = '1';
                                                $this->CreateConversationLog($messageRasa, $language, true);

                                                //cuando hay mas de 3 nlu_fallback y el modulo de agente esta activo
                                                // Y FALLA URL O OTRO FALLO
                                                $textRasa = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                                ->where('user', $result['usuario'])
                                                ->where('status', 1)
                                                ->whereNotNull('intention')
                                                ->where('intention', '!=', '')
                                                ->first();

                                                $this->updateOrCreateConversationIntention($conversation_id, null, $textRasa->question_citizen, $result['texto'], "abierta",4);

                                                $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                                ->where('user', $conversation_id)
                                                ->where('status', 1)
                                                ->get();
                                                foreach ($chatbotsRasaUp as $chatbotRasa) {
                                                    ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                                        'status' => 0
                                                    ]);
                                                }
                                            }
                                        }
                                    } else {
                                        $messageRasa['type_user'] = 'bot';
                                        $messageRasa['type'] = 'text';
                                        $messageRasa['idNode'] = $idNode->id;
                                        $messageRasa['chatbot_id'] = $chatbot_id;
                                        $messageRasa['end'] = '1';
                                        $messageRasa['conversation_id'] = $conversation_id;
                                        if($answerConceptError !==null){
                                            $messageRasa['message'] = $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                            ? $answerConceptError[0]->answer
                                            : $translations['no_he_entendido'][$language];
                                        }else{
                                            $messageRasa['message'] = $translations['no_he_entendido'][$language];
                                        }
                                        $this->CreateConversationLog($messageRasa, $language, true);

                                        //cuando hay mas de 3 nlu_fallback y el modulo de agente no esta activo
                                        $textRasa = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                        ->where('user', $result['usuario'])
                                        ->where('status', 1)
                                        ->whereNotNull('intention')
                                        ->where('intention', '!=', '')
                                        ->first();

                                        $this->updateOrCreateConversationIntention($conversation_id, null, $textRasa->question_citizen, $result['texto'], "abierta",4);

                                        $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                        ->where('user', $conversation_id)
                                        ->where('status', 1)
                                        ->get();
                                        foreach ($chatbotsRasaUp as $chatbotRasa) {
                                            ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                                'status' => 0
                                            ]);
                                        }
                                    }
                                } else {
                                    $messageRasa['type_user'] = 'bot';
                                    $messageRasa['type'] = 'text';
                                    $messageRasa['idNode'] = $idNode->id;
                                    $messageRasa['chatbot_id'] = $chatbot_id;
                                    $messageRasa['conversation_id'] = $conversation_id;
                                    if($answerConceptError !==null){
                                        $messageRasa['message'] = $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                        ? $answerConceptError[0]->answer
                                        : $translations['no_he_entendido'][$language];

                                        $messageRasa['dataFAQ'][] = ['text' => $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                        ? $answerConceptError[0]->answer
                                        : $translations['no_he_entendido'][$language], 'action' => 'free_question'];
                                    }else{
                                        $messageRasa['message'] = $translations['no_he_entendido'][$language];
                                        $messageRasa['dataFAQ'][] = ['text' => $translations['no_he_entendido'][$language], 'action' => 'free_question'];
                                    }
                                    $messageRasa['faq'] = 'faq';

                                    $this->CreateConversationLog($messageRasa, $language, true);
                                }
                            } else {
                                // Agregar la lógica condicional basada en el conteo nlu_fallback
                                if($ChatbotRasaNluFallbackConsecutive){
                                    $messageRasa['type_user'] = 'bot';
                                    $messageRasa['type'] = 'text';
                                    $messageRasa['idNode'] = $idNode->id;
                                    $messageRasa['chatbot_id'] = $chatbot_id;
                                    $messageRasa['conversation_id'] = $conversation_id;
                                    if($answerConceptError !==null){
                                        $messageRasa['message'] =  $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                        ? $answerConceptError[0]->answer
                                        :$result['texto'];
                                    }else{
                                        $messageRasa['message'] = $result['texto'];
                                    }
                                    $messageRasa['end'] = '1';
                                    $this->CreateConversationLog($messageRasa, $language, true);
                                }else{
                                    $messageRasa['type_user'] = 'bot';
                                    $messageRasa['type'] = 'text';
                                    $messageRasa['idNode'] = $idNode->id;
                                    $messageRasa['chatbot_id'] = $chatbot_id;
                                    $messageRasa['faq'] = 'faq';
                                    $messageRasa['conversation_id'] = $conversation_id;
                                    if($answerConceptError !==null){
                                        $messageRasa['dataFAQ'][] = ['text' =>  $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                        ? $answerConceptError[0]->answer
                                        :$result['texto'], 'action' => 'free_question'];
                                        $messageRasa['message'] =  $answerConceptError->isNotEmpty() && isset($answerConceptError[0]->answer)
                                        ? $answerConceptError[0]->answer
                                        :$result['texto'];
                                    }else{
                                        $messageRasa['dataFAQ'][] = ['text' => $result['texto'], 'action' => 'free_question'];
                                        $messageRasa['message'] = $result['texto'];
                                    }
                                    $this->CreateConversationLog($messageRasa, $language, true);
                                }
                            }
                            if ($result['form'] == null) {
                                $this->updateOrCreateConversationIntention($conversation_id, null, $typeFAQ['question'], $result['texto'], "abierta",4);

                                $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                ->where('user', $conversation_id)
                                ->where('status', 1)
                                ->get();
                                foreach ($chatbotsRasaUp as $chatbotRasa) {
                                    ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                        'status' => 0
                                    ]);
                                }
                            }
                            return;

                        } elseif ($result['intencion'] === 'desvio_agente'  || $calificacionPositivaNegativa === true) {
                            $chatbot = ChatbotSetting::with('defaultTable')
                                ->withTrashed()
                                ->where('chatbot_id', $chatbot_id)
                                ->whereHas('defaultTable', function ($query) {
                                    $query->whereIn('name', ['modulo_agente']);
                                })
                                ->value('value');
                            if ($chatbot === "1") {
                                $desvioAgente = $this->desvioAgente($chatbot_id, $language, $result['usuario']);
                                if ($desvioAgente['success']) {
                                    if (isset($desvioAgente['message'])) {
                                        $no_follow_flow = true;
                                        $messageRasa['type_user'] = 'bot';
                                        $messageRasa['type'] = 'text';
                                        $messageRasa['idNode'] = null;
                                        $messageRasa['chatbot_id'] = $chatbot_id;
                                        $messageRasa['conversation_id'] = $conversation_id;
                                        $messageRasa['message'] = $desvioAgente['message'];
                                        $messageRasa['faq'] = 'faq';
                                        $messageRasa['dataFAQ'][] = ['text' => $desvioAgente['message'], 'action' => 'free_question'];
                                        $this->CreateConversationLog($messageRasa, $language, true);
                                    }
                                } else {
                                    if (isset($desvioAgente['message'])) {
                                        //festivos y fuera de horario, se cierra conversacion si falla
                                        $messageRasa['type_user'] = 'bot';
                                        $messageRasa['type'] = 'text';
                                        $messageRasa['idNode'] = $idNode->id;
                                        $messageRasa['chatbot_id'] = $chatbot_id;
                                        $messageRasa['conversation_id'] = $conversation_id;
                                        $messageRasa['message'] = $desvioAgente['message'];
                                        $messageRasa['end'] = '1';
                                        $this->CreateConversationLog($messageRasa, $language, true);
                                    } else {
                                        //fallos por url o otros fallos y cierra la conversacion
                                        if (isset($desvioAgente['reason']) && $desvioAgente['reason'] == 'URL') {
                                            Log::error('error desvio agente', ['url' => $desvioAgente['error']]);
                                        }
                                        $messageRasa['type_user'] = 'bot';
                                        $messageRasa['type'] = 'text';
                                        $messageRasa['idNode'] = $idNode->id;
                                        $messageRasa['chatbot_id'] = $chatbot_id;
                                        $messageRasa['conversation_id'] = $conversation_id;
                                        $messageRasa['message'] = $translations['no_he_entendido'][$language];
                                        $messageRasa['end'] = '1';
                                        $this->CreateConversationLog($messageRasa, $language, true);
                                    }
                                }
                            } else {
                                //Cancelamos la conversacion si el modulo_agente no esta activo
                                $messageRasa['type_user'] = 'bot';
                                $messageRasa['type'] = 'text';
                                $messageRasa['idNode'] = $idNode->id;
                                $messageRasa['chatbot_id'] = $chatbot_id;
                                $messageRasa['conversation_id'] = $conversation_id;
                                $messageRasa['message'] = $translations['no_he_entendido'][$language];
                                $messageRasa['end'] = '1';
                                $this->CreateConversationLog($messageRasa, $language, true);
                            }
                            $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                            ->where('user', $conversation_id)
                            ->where('status', 1)
                            ->get();
                            foreach ($chatbotsRasaUp as $chatbotRasa) {
                                ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                    'status' => 0
                                ]);
                            }
                            return;
                        } elseif ($result['intencion'] === 'cancelar') {

                            $messageRasa['type_user'] = 'bot';
                            $messageRasa['type'] = 'text';
                            $messageRasa['idNode'] = null;
                            $messageRasa['chatbot_id'] = $chatbot_id;
                            $messageRasa['end'] = '1';
                            $messageRasa['conversation_id'] = $conversation_id;
                            $messageRasa['message'] = $result['texto'];
                            $this->CreateConversationLog($messageRasa, $language, true);

                            $chatbotData = ChatbotRasa::where('chatbot_id', $chatbot_id)
                            ->whereNotNull('intention')
                            ->where('intention', '!=', '')
                            ->where('intention', 'cancelar')
                            ->where('user', $result['usuario'])
                            ->where('status', 1);

                            $intentionChatbotRasa = $chatbotData->orderBy('created_at', 'asc')->value('intention');
                            $intentionId = Intentions::where('chatbot_id', $chatbot_id)->where('name', $intentionChatbotRasa)->value('id');

                            $this->updateOrCreateConversationIntention($conversation_id, null, null,$result['texto'],"abierta",2);

                            $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                            ->where('user', $conversation_id)
                            ->where('status', 1)
                            ->get();
                            foreach ($chatbotsRasaUp as $chatbotRasa) {
                                ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                    'status' => 0
                                ]);
                            }
                            return;
                        } elseif ($result['intencion'] === 'FORMULARIO_TERMINADO') {
                            if (isset($result['slots']) && is_array($result['slots']) && !empty($result['slots'])) {

                                $chatbotDataQuery = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                ->whereNotNull('intention')
                                ->where('intention', '!=', '')
                                ->where('user', $result['usuario'])
                                ->where('status', 1);

                                $chatbotData = $chatbotDataQuery->get();

                                if ($chatbotData->isEmpty()) {
                                    Log::warning('No se han encontrado datos del chatbot');
                                } else {
                                    if(isset($result['form_terminado']) && !empty($result['form_terminado'])){
                                        $form_terminado = str_replace('_form', '',$result['form_terminado']);
                                        $intentionId = Intentions::where('chatbot_id', $chatbot_id)
                                            ->where('name', $form_terminado)
                                            ->value('id');
                                    }else{
                                        $intentionChatbotRasa = $chatbotData->whereNotNull('form')->sortBy('created_at')->first()->intention;
                                        $intentionId = Intentions::where('chatbot_id', $chatbot_id)
                                        ->where('name', $intentionChatbotRasa)
                                        ->value('id');
                                    }
                                    $slotsChatbotRasa = $chatbotData->where('intention', 'FORMULARIO_TERMINADO')->sortByDesc('created_at')->first()->slots;
                                }

                                $slotsData = json_decode($slotsChatbotRasa, true);

                                // Filtrar los datos eliminando las entradas con valores null
                                $filteredSlotsData = array_filter($slotsData, function($value) {
                                    return !is_null($value);
                                });

                                // Obtener los valores de los slots filtrados
                                $slotsDataValues = array_values($filteredSlotsData);
                                // Obtener las claves de los slots filtrados
                                $slotsDatakeys = array_keys($filteredSlotsData);

                                $partialCombination = ResCombination::select('combination_id', 'value', 'response')
                                    ->where('intentions_id', $intentionId)
                                    ->whereIn('value', $slotsDataValues)
                                    ->get();

                                // Filtrar combinaciones completas
                                $resCombination = $partialCombination->groupBy('combination_id')->filter(function ($group) use ($slotsDataValues) {
                                    $groupValues = $group->pluck('value')->sort()->values()->all();
                                    $sortedSlotsDataValues = collect($slotsDataValues)->sort()->values()->all();
                                    return $groupValues === $sortedSlotsDataValues;
                                });

                                // Obtener los resultados finales
                                //$finalResults = $resCombination->flatten();
                                $hasResponseOne = false;
                                foreach ($resCombination as $combination) {
                                    foreach ($combination as $item) {
                                        if ($item['response'] == 1) {
                                            $hasResponseOne = true;
                                            break 2;
                                        }
                                    }
                                }

                                $answerType = $hasResponseOne ? 1 : 2;
                                $answers = Answers::where('type', $answerType)
                                    ->where('intentions_id', $intentionId)
                                    ->with(['answersLanguage' => function ($query) use ($language) {
                                        $query->where('language', $language);
                                    }])
                                    ->get();

                                foreach ($answers as $answer) {
                                    foreach ($answer->answersLanguage as $answerLanguage) {
                                        $messageRasa['type_user'] = 'bot';
                                        $messageRasa['type'] = 'text';
                                        $messageRasa['idNode'] = $idNode->id;
                                        $messageRasa['chatbot_id'] = $chatbot_id;
                                        $messageRasa['conversation_id'] = $conversation_id;

                                        // Reemplazar las llaves con los valores correspondientes
                                        $search = array_map(function ($key) {
                                            return '{' . $key . '}';
                                        }, $slotsDatakeys);
                                        $replace = $slotsDataValues;

                                        $messageRasa['message'] = str_replace($search, $replace, $answerLanguage->answers);
                                        $textoAnswerIntention = $messageRasa['message'];

                                        $this->CreateConversationLog($messageRasa, $language, true);
                                    }
                                }

                                $textRasa = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                ->where('user', $result['usuario'])
                                ->where('status', 1)
                                ->whereNotNull('intention')
                                ->where('intention', '!=', '')
                                ->first();
                                $this->updateOrCreateConversationIntention($conversation_id, $intentionId,$textRasa->question_citizen, $textoAnswerIntention, "abierta", 2);

                                $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                                ->where('user', $conversation_id)
                                ->where('status', 1)
                                ->get();
                                foreach ($chatbotsRasaUp as $chatbotRasa) {
                                    ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                        'status' => 0
                                    ]);
                                }
                            }

                        } else {
                            $messageRasa['type_user'] = 'bot';
                            $messageRasa['type'] = 'text';
                            $messageRasa['idNode'] = $idNode->id;
                            $messageRasa['chatbot_id'] = $chatbot_id;
                            $messageRasa['faq'] = 'faq';
                            $messageRasa['conversation_id'] = $conversation_id;
                            $messageRasa['dataFAQ'][] = ['text' => $result['texto'], 'action' => 'free_question'];
                            $messageRasa['message'] = $result['texto'];
                            $this->CreateConversationLog($messageRasa, $language, true);
                        }
                    }

                    //INTENCIONES SIMPLE
                    if (isset($result['intencion']) && $result['intencion'] != null && $result['form'] == null && !($result['intencion'] === 'mood_great' || $result['intencion'] === "bot_challenge" || $result['intencion'] === "greet" || $result['intencion'] === "goodbye" || $result['intencion'] === "affirm" || $result['intencion'] === 'nlu_fallback' || $result['intencion'] === 'desvio_agente' || $result['intencion'] === 'cancelar' || $result['intencion'] === 'FORMULARIO_TERMINADO' || $result['intencion'] === "deny" || $result['intencion'] === "mood_unhappy")) {
                        $chatbotDataQuery = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->whereNotNull('intention')
                        ->where('intention', '!=', '')
                        ->where('user', $result['usuario']);
                        $chatbotData = $chatbotDataQuery->get();

                        $intentionChatbotRasa = $chatbotData->sortBy('created_at')->first()->intention;
                        $intentionId = Intentions::where('chatbot_id', $chatbot_id)
                        ->where('name', $intentionChatbotRasa)
                        ->value('id');
                        $this->updateOrCreateConversationIntention($conversation_id, $intentionId, $typeFAQ['question'], $result['texto'], "abierta",2);

                        $chatbotsRasaUp = ChatbotRasa::where('chatbot_id', $chatbot_id)
                        ->where('user', $conversation_id)
                        ->where('status', 1)
                        ->get();

                        foreach ($chatbotsRasaUp as $chatbotRasa) {
                            ChatbotRasa::where('id', $chatbotRasa->id)->update([
                                'status' => 0
                            ]);
                        }
                    }
                    //Validacion para verificar si el nodo que sigue es validate_response y poder actualizar la conversacion
                    //con la califacion del cuidadano
                    $getNodeEnd = $this->getNodeEnd($chatbot_id,$idNode->id);
                    $getNodeTransition = $this->getNodeTransition($chatbot_id, $getNodeEnd->node);
                    $getNodeValidateResponse = $this->getNodeValidateResponse($chatbot_id, $getNodeTransition->destination);

                    if ($getNodeValidateResponse) {
                        if($result['form'] == null || $result['intencion'] === 'FORMULARIO_TERMINADO'){
                            $chatbotRasa->validate_response = "validate_response";
                            $chatbotRasa->save();
                        }
                    }
                    //Si obtengo un formulario tengo que seguir a la IA
                    if (isset($result['form'])) {
                        return;
                    }
                }
            } elseif ($typeFAQ['type'] === "faq" && isset($typeFAQ['intention_id'])) {
                //typeFAQ que no va a la IA
                $dataIntention = Intentions::with('answers.answersLanguage', 'questions.questionLanguages')->where('id', $typeFAQ['intention_id'])->get();
                // Acceder a los datos de answersLanguage y questionLanguage
                foreach ($dataIntention as $intention) {
                    $randomAnswers = $intention->answers->random();
                    $question = $intention->questions->first();
                    $answersLanguage = $this->getSortedFirstLanguage($randomAnswers->answersLanguage, $language);
                    $questionLanguage = $this->getSortedFirstLanguage($question->questionLanguages, $language);
                    $dataAnswersLanguage['conversation_id'] = $conversation_id;
                    $dataAnswersLanguage['message'] = $answersLanguage->answers;
                    $dataAnswersLanguage['type_user'] = "bot";
                    $dataAnswersLanguage['type'] = 'text';
                    $dataAnswersLanguage['idNode'] = $idNode->id;
                    $dataAnswersLanguage['chatbot_id'] = $chatbot_id;
                    $logResultCustomer = $this->CreateConversationLog($dataAnswersLanguage, $language, true);
                    if ($logResultCustomer === null) {
                        throw new \ErrorException('No se pudo crear el registro de ConversationLog');
                        return response()->json(['error' => 'No se pudo crear el registro de ConversationLog'], 500);
                    }
                    $this->addConversationIntention($conversation_id, $typeFAQ['intention_id'],$questionLanguage->question,$answersLanguage->answers,"cerrada", null);
                }
            } else {
                throw new \ErrorException('typeFAQ no es valido');
                return response()->json(['error' => 'typeFAQ no es valido'], 400);
            }
        }
        if (!$no_follow_flow) {
            if (isset($selection)) {
                $result = $this->chat_next_transition($node, $chatbot_id, $selection);
            } elseif (isset($message)) {
                $result = $this->chat_next_transition($node, $chatbot_id, "*");
            } else {
                return response()->json(['error' => 'selection o message son obligatorios'], 400);
            }

            if ($result->result === 0) {
                return response()->json(['error' => 'El flujo del chatbot no es válido'], 400);
            }

            do {

                if (isset($result->result) && $result->result !== null) {
                    $nextNode = $result->result;
                } else {
                    $nextNode = $resultNode->result;
                }

                $getNextNode = $this->getNextNode($chatbot_id, $nextNode, $language);
                $otherIdNode = $this->getIdNode($chatbot_id, $nextNode);
                $nodeText = "";
                if ($getNextNode['typenode'] === "faq") {
                    $logGetNextNode['faq'] = "faq";
                    $nodeFAQ = $this->nodeFAQ($chatbot_id, $nextNode, $language);
                    if ($nodeFAQ && $nodeFAQ->isNotEmpty()) {
                        foreach ($nodeFAQ as $node) {
                            if ($node->nodeIntentions && !$node->nodeIntentions->isEmpty()) {
                                // Acceder a la relación nodeIntentions
                                foreach ($node->nodeIntentions as $intention) {
                                    // Verificar si la relación intentionLanguages está cargada
                                    if ($intention->intention && $intention->intention->relationLoaded('intentionLanguages')) {
                                        $nodeText .= "Intención:" . $intention->intention->intentionLanguages[0]->name . "\n";
                                    }
                                    $logGetNextNode['dataFAQ'][] = ['intention_id' => $intention->intention->id, 'text' => $intention->intention->intentionLanguages[0]->name, 'action' => 'faq'];
                                }
                            }
                            if ($node->nodeLanguages && !$node->nodeLanguages->isEmpty()) {
                                //--------------Acaaaa va ir a la IAAAAAAAAAAAAAAAA -------------------------------------
                                $nodeText .= "Pregunta abierta: " . $node->nodeLanguages[0]->text . "\n";
                                $logGetNextNode['dataFAQ'][] = ['text' => $node->nodeLanguages[0]->text, 'action' => 'free_question'];
                            }
                        }
                    }
                } else {
                    $nodeText = $getNextNode->nodeLanguages[0]->text;
                }


                switch ($getNextNode->typenode) {
                    case 'privacy_policy':
                        $logGetNextNode['privacy_policy'] = 'privacy_policy';
                        break;
                    case 'validate_response':
                        $logGetNextNode['validate_response'] = 'validate_response';
                        break;
                    case 'new_inquiry':
                        $logGetNextNode['new_inquiry'] = 'new_inquiry';
                        break;
                    case 'language':
                        $logGetNextNode['language'] = 'language';
                        break;
                    case 'end':
                        $logGetNextNode['end'] = '1'; // Agregar indicador de END
                        break;
                        // case 'end':
                        //     $logGetNextNode['end'] = '1'; // Agregar indicador de END
                        //     break;
                }

                //consulta resulta, nueva consulta
                $logGetNextNode['conversation_id'] = $conversation_id;
                $logGetNextNode['message'] = $nodeText;
                $logGetNextNode['type_user'] = "bot";
                $logGetNextNode['node'] = $nextNode;
                $logGetNextNode['type'] = 'text';
                $logGetNextNode['idNode'] = $otherIdNode->id;
                $logGetNextNode['chatbot_id'] = $chatbot_id;

                $logResult = $this->CreateConversationLog($logGetNextNode, $language, true);
                if ($logResult === null) {
                    return response()->json(['error' => 'No se pudo crear el registro de ConversationLog'], 500);
                }

                // Obtener el siguiente nodo

                if (!in_array($getNextNode->typenode, ['privacy_policy', 'validate_response', 'new_inquiry', 'end', 'language', 'faq'])) {
                    $resultNode = $this->chat_next_transition($getNextNode->node, $chatbot_id, "*");
                } else {
                    break;
                }
                $result = $this->getNextNode($chatbot_id, $resultNode->result, $language);
            } while (!$this->dataTypeNode($chatbot_id, $result->result));
        }
    }


    // Lógica para procesar nueva conversación
    private function processNewConversation($conversation_id, $node, $message, $selection, $type_user, $chatbot_id, $language, $typeFAQ)
    {
        //id del primer nodo
        $idNode = $this->getIdNode($chatbot_id, null);
        //Primer nodo
        $getNodeStart = $this->getNodeStart($chatbot_id, $language);
        $dataGetNodeStart['conversation_id'] = $conversation_id;
        $dataGetNodeStart['message'] = $getNodeStart->nodeLanguages[0]->text;
        $dataGetNodeStart['type_user'] = "bot";
        $dataGetNodeStart['type'] = "text";
        $dataGetNodeStart['node'] = $getNodeStart->node;
        $dataGetNodeStart['idNode'] = $idNode->id;
        $dataGetNodeStart['chatbot_id'] = $chatbot_id;

        if ($language != null) {
            $this->updateConversationLanguage($conversation_id, $language);
        }
        //Registro de log primer mensaje
        $logResult = $this->CreateConversationLog($dataGetNodeStart, $language, true);
        if ($logResult === null) {
            return response()->json(['error' => 'No se pudo crear el registro de ConversationLog Nuevo'], 500);
        }

        //chat_start_transition
        $result = $this->chat_start_transition($chatbot_id);
        if ($result->result === 0) {
            return response()->json(['error' => 'El flujo del chatbot no es válido'], 400);
        }

        // Procesar hasta que dataTypeNode() devuelva false
        // Procesar al menos una vez y luego continuar mientras dataTypeNode() devuelva true
        do {
            // Registro de log mensaje siguiente
            // Verificar si $result->result está definido y no es nulo
            if (isset($result->result) && $result->result !== null) {
                $nextNode = $result->result;
            } else {
                $nextNode = $resultNode->result;
            }

            $otherIdNode = $this->getIdNode($chatbot_id, $nextNode);
            $getNextNode = $this->getNextNode($chatbot_id, $nextNode, $language);
            $nodeText = "";
            if ($getNextNode['typenode'] === "faq") {
                $dataGetNextNode['faq'] = "faq";
                $nodeFAQ = $this->nodeFAQ($chatbot_id, $nextNode, $language);
                if ($nodeFAQ && $nodeFAQ->isNotEmpty()) {
                    foreach ($nodeFAQ as $node) {
                        if ($node->nodeLanguages && !$node->nodeLanguages->isEmpty()) {
                            //--------------Acaaaa va ir a la IAAAAAAAAAAAAAAAA -------------------------------------
                            $nodeText .= "Pregunta abierta: " . $node->nodeLanguages[0]->text . "\n";
                            $dataGetNextNode['dataFAQ'][] = ['text' => $node->nodeLanguages[0]->text, 'action' => 'free_question'];
                        }
                        if ($node->nodeIntentions && !$node->nodeIntentions->isEmpty()) {
                            // Acceder a la relación nodeIntentions
                            foreach ($node->nodeIntentions as $intention) {
                                // Verificar si la relación intentionLanguages está cargada
                                if ($intention->intention && $intention->intention->relationLoaded('intentionLanguages')) {
                                    $nodeText .= "Intención:" . $intention->intention->intentionLanguages[0]->name . "\n";
                                }
                                $dataGetNextNode['dataFAQ'][] = ['intention_id' => $intention->intention->id, 'text' => $intention->intention->intentionLanguages[0]->name, 'action' => 'faq'];
                            }
                        }
                    }
                }
            } else {
                $nodeText = $getNextNode->nodeLanguages[0]->text;
            }

            if ($getNextNode->typenode === 'privacy_policy') {
                $dataGetNextNode['privacy_policy'] = "privacy_policy";
            }

            $dataGetNextNode['conversation_id'] = $conversation_id;
            $dataGetNextNode['message'] = $nodeText;
            $dataGetNextNode['type_user'] = "bot";
            $dataGetNextNode['node'] = $getNextNode->node;
            $dataGetNextNode['type'] = $getNextNode->typenode;
            $dataGetNextNode['idNode'] = $otherIdNode->id;
            $dataGetNextNode['chatbot_id'] = $chatbot_id;

            $logResult = $this->CreateConversationLog($dataGetNextNode, $language, true);
            if ($logResult === null) {
                return response()->json(['error' => 'No se pudo crear el registro de ConversationLog segundo mensaje'], 500);
            }

            // Obtener el siguiente nodo
            if (!in_array($getNextNode->typenode, ['privacy_policy', 'validate_response', 'new_inquiry', 'end', 'language', 'faq'])) {
                $resultNode = $this->chat_next_transition($getNextNode->node, $chatbot_id, "*");
            } else {
                break;
            }

            $result = $this->getNextNode($chatbot_id, $resultNode->result, $language);
        } while (!$this->dataTypeNode($chatbot_id, $result->result));
    }

    private function processAgentConversation($conversation_id, $message, $chatbot_id, $type_user, $language)
    {
        $conversation = Conversation::where('id', $conversation_id)->first();
        $conversation->agent = 1;
        $conversation->save();

        $messageAgent['conversation_id'] = $conversation_id;
        $messageAgent['message'] = $message;
        $messageAgent['type_user'] = $type_user;
        $messageAgent['type'] = 'text';
        $messageAgent['chatbot_id'] = $chatbot_id;
        $messageAgent['idNode'] = null;


        $this->CreateConversationLog($messageAgent, $language, true);
    }

    function addConversationIntention($conversation_id, $intention_id, $question, $answer, $type, $training_status_id)
    {
        try {
            $conversationIntention = new ConversationIntention;
            $conversationIntention->conversation_id = $conversation_id;
            $conversationIntention->intention_id = $intention_id;
            $conversationIntention->question = $question;
            $conversationIntention->answer = $answer;
            $conversationIntention->type = $type;
            $conversationIntention->training_status_id = $training_status_id;
            $conversationIntention->save();

            // Verificar si el guardado fue exitoso
            if (!$conversationIntention->wasRecentlyCreated) {
                throw new Exception("Error al guardar la ConversationIntention de conversación.");
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar errores relacionados con la base de datos
            return "Error en la base de datos: ConversationIntention" . $e->getMessage();
        } catch (Exception $e) {
            // Capturar otros tipos de excepciones
            return "Se produjo un error: ConversationIntention" . $e->getMessage();
        }
    }

    function updateConversationIntention($chatbot_id, $conversation_id, $training_status_id)
    {
        try {
            // Buscar el registro existente usando el conversation_id
            $conversationIntention = ConversationIntention::where('conversation_id', $conversation_id)
            ->latest('created_at')
            ->first();

            // Verificar si el registro existe
            if ($conversationIntention) {
                // Actualizar los campos necesarios
                $conversationIntention->training_status_id = $training_status_id;
                $conversationIntention->save();
            } else {
                throw new Exception("No se encontró el registro de ConversationIntention con conversation_id: $conversation_id");
            }
        } catch (\Illuminate\Database\QueryException $e) {
            // Capturar errores relacionados con la base de datos
            return "Error en la base de datos: ConversationIntention - " . $e->getMessage();
        } catch (Exception $e) {
            // Capturar otros tipos de excepciones
            return "Se produjo un error: ConversationIntention - " . $e->getMessage();
        }
    }


    function updateOrCreateConversationIntention($conversation_id, $intention_id, $question, $answer, $type, $training_status_id, $nlu_fallback = null)
    {
        try {
            // Obtener el registro existente
            if($nlu_fallback !=null){
                $existingRecord = ConversationIntention::where('conversation_id', $conversation_id)->first();
            }else{
                $existingRecord = ConversationIntention::where('conversation_id', $conversation_id)
                ->where('training_status_id', $training_status_id)->first();
            }

            // Si no existe un registro, crear uno nuevo
            if (!$existingRecord) {
                ConversationIntention::create([
                    'conversation_id' => $conversation_id,
                    'intention_id' => $intention_id,
                    'question' => $question,
                    'answer' => $answer,
                    'type' => $type,
                    'training_status_id' => $training_status_id
                ]);
            } else {
                // Si existe un registro, actualizar solo los campos no nulos
                $updateData = [];

                if (!is_null($intention_id)) {
                    $updateData['intention_id'] = $intention_id;
                }
                if (!is_null($question)) {
                    $updateData['question'] = $question;
                }
                if (!is_null($answer)) {
                    $updateData['answer'] = $answer;
                }
                if (!is_null($type)) {
                    $updateData['type'] = $type;
                }
                if (!is_null($training_status_id)) {
                    $updateData['training_status_id'] = $training_status_id;
                }

                // Solo actualizar si hay datos para actualizar
                if (!empty($updateData)) {
                    $existingRecord->update($updateData);
                }
            }
        } catch (\Exception $e) {
            Log::error("Error in updateOrCreateConversationIntention", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // Función para obtener el ID del nodo
    function getIdNode($chatbot_id, $node = null)
    {
        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;

        if ($node === null) {
            // Si no se proporciona un nodo específico, obtén el primero para el chatbot_log_id dado
            return Node::select('id')->where('chatbot_log_id', $chatbotLogID)->first();
        } else {
            // Si se proporciona un nodo específico, obtén ese nodo para el chatbot_log_id dado
            return Node::select('id')->where('node', $node)->where('chatbot_log_id', $chatbotLogID)->first();
        }
    }

    // Función para obtener el typeNode segun el id del nodo
    function dataTypeNode($chatbot_id, $node = null)
    {

        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;
        if ($node === null) {
            $node = Node::where('chatbot_log_id', $chatbotLogID)->first();
        } else {
            $node = Node::where('node', $node)
                ->whereIn('typenode', ['privacy_policy', 'validate_response', 'new_inquiry', 'language'])
                ->where('chatbot_log_id', $chatbotLogID)
                ->first();
        }
        return $node !== null && in_array($node->typenode, ['privacy_policy', 'validate_response', 'new_inquiry']);
    }


    //Paso Inicial
    public function chat_start_transition($chatbot_id)
    {
        $dataNode = $this->getNodeStart($chatbot_id)->node;
        $result = DB::selectOne("SELECT chat_next_transition(:w_current_state, :w_text, :w_chatbot_log_id) AS result", [
            'w_current_state' => $dataNode,
            'w_text' => '*',
            'w_chatbot_log_id' => $this->latestChatbotLog($chatbot_id)->id,
        ]);
        return $result;
    }

    //Paso Siguiente
    public function chat_next_transition($node, $chatbot_id, $text)
    {
        $result = DB::selectOne("SELECT chat_next_transition(:w_current_state, :w_text, :w_chatbot_log_id) AS result", [
            'w_current_state' => $node,
            'w_text' => $text,
            'w_chatbot_log_id' => $this->latestChatbotLog($chatbot_id)->id,
        ]);
        return $result;
    }

    public function getNodeTransition($chatbot_id, $origin)
    {
        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;

        $dataNode = NodeTransition::where('chatbot_log_id', $chatbotLogID)
            ->where('origin', $origin)
            ->first(['id', 'destination']);

        return $dataNode;
    }
    public function getNodeEnd($chatbot_id, $id)
    {
        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;

        $dataNode = Node::where('chatbot_log_id', $chatbotLogID)
            ->where('id', $id)
            ->where('name', 'FAQ')
            ->first(['id', 'node']);

        return $dataNode;
    }
    public function getNodeValidateResponse($chatbot_id, $destination)
    {
        // Verificar que $chatbot_id y $destination no estén vacíos o sean nulos
        if (empty($chatbot_id) || empty($destination)) {
            Log::error('Invalid parameters provided to getNodeValidateResponse', ["chatbot_id" => $chatbot_id, "destination" => $destination]);
            return null;
        }

        // Obtener el último log del chatbot
        $latestLog = $this->latestChatbotLog($chatbot_id);
        if (!$latestLog) {
            Log::error('No chatbot log found for the provided chatbot_id', ["chatbot_id" => $chatbot_id]);
            return null;
        }

        $chatbotLogID = $latestLog->id;

        // Realizar la consulta para obtener el nodo
        $dataNode = Node::where('chatbot_log_id', $chatbotLogID)
            ->where('node', $destination)
            ->where('typenode', 'validate_response')
            ->first();

        // Verificar si se encontró el nodo
        if (!$dataNode) {
            return false;
        }

        return true;
    }

    public function getNodeStart($chatbot_id, $language = null)
    {
        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;
        $lang = $this->getLanguage($chatbot_id, $language);

        $dataNode = Node::where('chatbot_log_id', $chatbotLogID)
            ->where('name', 'Start')
            ->with(['nodeLanguages' => function ($query) use ($lang) {
                $query->select('id', 'node_id', 'language', 'text')->orderByRaw("language = '$lang' DESC");
            }])
            ->first(['id', 'node']);;
        return $dataNode;
    }

    public function getNextNode($chatbot_id, $id, $language = null)
    {
        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;
        $lang = $this->getLanguage($chatbot_id, $language);

        $dataNode = Node::where('chatbot_log_id', $chatbotLogID)
            ->where('node', $id)
            ->with(['nodeLanguages' => function ($query) use ($lang) {
                $query->select('id', 'node_id', 'language', 'text')->orderByRaw("language = '$lang' DESC");
            }])
            ->first(['id', 'node', 'typenode']);
        return $dataNode;
    }

    public function nodeFAQ($chatbot_id, $id, $language)
    {
        $chatbotLogID = $this->latestChatbotLog($chatbot_id)->id;
        $lang = $this->getLanguage($chatbot_id, $language);
        $dataNode = Node::where('chatbot_log_id', $chatbotLogID)
            ->where('node', $id)
            ->with(['nodeLanguages' => function ($query) use ($lang) {
                $query->select('id', 'node_id', 'language', 'text')->orderByRaw("language = '$lang' DESC");
            }])
            ->with(['nodeIntentions' => function ($query) use ($lang) {
                $query->with(['intention' => function ($query) use ($lang) {
                    $query->with(['intentionLanguages' => function ($query) use ($lang) {
                        $query->select('id', 'name', 'language', 'intention_id')->orderByRaw("language = '$lang' DESC");
                    }]);
                }]);
            }])
            ->get();

        return $dataNode;
    }

    /**
     * Crea una nueva conversación para un chatbot.
     *
     * Este método crea una nueva conversación para un chatbot específico, iniciando un nuevo hilo de interacción.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|int
     *
     * @OA\Post(
     *     path="/api/createConversation",
     *     tags={"conversation"},
     *     summary="Crear una nueva conversación para un chatbot.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos requeridos para crear una nueva conversación.",
     *         @OA\JsonContent(
     *             required={"chatbot_id"},
     *             @OA\Property(property="chatbot_id", type="string", example="b46c09c0-f785-4aca-a5f6-f29158ce3dac")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="ID de la conversación creada exitosamente.",
     *         @OA\JsonContent(
     *             type="integer",
     *             example="123"
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en los datos de entrada."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El chatbot no fue encontrado o su ID no es válido."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor."
     *     )
     * )
     */
    public function CreateConversation(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'chatbot_id' => [
                    'required',
                    Rule::exists('chatbots', 'id')->where(function ($query) {
                        // Aquí especificamos que el campo 'id' en la tabla 'chatbots' debe ser un UUID
                        $query->where('id', 'REGEXP', '[[:xdigit:]]{8}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{4}-[[:xdigit:]]{12}');
                    }),
                ],
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }
            $chatbot_id = $request->input('chatbot_id');

            // dd($chatbot_id);
            $latestChatbotLog = $this->latestChatbotLog($chatbot_id);

            if (!$latestChatbotLog) {
                throw new \Exception('No se encontró el chatbot_log correspondiente.');
            }

            $chatbotLogID = $latestChatbotLog->id;
            $language = $this->getLanguage($chatbot_id, null);
            $conversationData = new Conversation();
            $conversationData->finished = 0;
            $conversationData->agent = 0;
            $conversationData->chatbot_log_id = $chatbotLogID;
            $conversationData->language = $language;
            $conversationData->save();

            return $conversationData->id;
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function CreateConversationLog($dataInsert, $language, $emitEvent = true)
    {
        // crear el ConversationLog
        $conversationLog = new ConversationLog();
        $conversationLog->conversation_id = $dataInsert['conversation_id'];
        $conversationLog->message = $dataInsert['message'];
        $conversationLog->type_user = $dataInsert['type_user'];
        $conversationLog->node_id = $dataInsert['idNode'];
        $saved = $conversationLog->save();

        $data['conversation_id'] = $dataInsert['conversation_id'];
        $data['node'] = $dataInsert['node'] ?? null;
        $data['message']['type'] = 'text';
        $data['message']['data']['text'] = $dataInsert['message'];

        if ($dataInsert['type_user'] === 'agente') {
            $data['message']['author'] = 'agente';
        } else {
            $data['message']['author'] = 'bot';
            $translations = $this->getTranslations();
            $language = $this->getLanguage($dataInsert['chatbot_id'], $language);

            if (isset($dataInsert['privacy_policy'])) {
                $data['message']['suggestions'] = [$translations['agree'][$language], $translations['disagree'][$language]];
            } elseif (isset($dataInsert['validate_response']) || isset($dataInsert['new_inquiry'])) {
                $data['message']['suggestions'] = [$translations['yes'][$language], $translations['no'][$language]];
            } elseif (isset($dataInsert['type']) && $dataInsert['type'] == 'language') {
                $languagesSettings = $this->getLanguageSettings($dataInsert['conversation_id']);
                $languageResponse = array_map(function ($value) use ($translations, $language) {
                    return $translations[$value][$language];
                }, $languagesSettings);

                $data['message']['suggestions'] = $languageResponse;
                $data['message']['typeNode'] = $dataInsert['type'];
            } elseif (isset($dataInsert['end'])) {
                $conversation = Conversation::find($dataInsert['conversation_id']);
                $conversationStatus = ConversationStatus::where('name', 'Finalizada')->value('id');
                if ($conversation) {
                    $conversation->finished = 1;
                    $conversation->conversation_status_id = $conversationStatus;
                    $conversation->save();
                }
                $data['end'] = "1";
            } elseif (isset($dataInsert['faq'])) {
                $faqData = Arr::get($dataInsert, 'dataFAQ', []);
                if (count($faqData) == 1 && $faqData[0]['action'] == 'free_question') {
                    $data['message']['data'] = [
                        'action' => $faqData[0]['action'],
                        'text' => $faqData[0]['text']
                    ];
                } else {
                    $data['message']['data']['text'] = '';
                    $data['message']['actions'] = $faqData;
                }
            }
        }

        // Si se creó el ConversationLog, emite el evento
        if ($saved) {
            if ($emitEvent) {
                EventConversation::dispatch($dataInsert['conversation_id'], $data);
            }
            return $conversationLog->id;
        }

        return null;
    }

    public function latestChatbotLog($chatbot_id)
    {
        $chatbotLog = ChatbotLog::where('chatbot_id', $chatbot_id)
            ->latest('created_at')
            ->first(['id', 'chatbot_id', 'flow']);
        return $chatbotLog;
    }

    public function getLanguage($chatbot_id, $language)
    {
        $lang = null;
        if ($language === "castellano" || $language === "ingles" || $language === "valenciano") {
            $lang = $language;
        } else {
            $chatbot = Chatbot::find($chatbot_id);
            $chatbotLang = $chatbot->settings()->whereHas('defaultTable', function ($query) {
                $query->where('name', 'idioma_principal');
            })->first();
            $lang = $chatbotLang->value;
        }
        return $lang;
    }

    public function updateConversationLanguage($conversation_id, $language)
    {
        $conversation = Conversation::find($conversation_id);

        if ($conversation) {
            if ($conversation->language != $language) {
                $conversation->language = $language;
                $conversation->save();
                return $conversation;
            } else {
                return "El lenguaje proporcionado es igual al lenguaje actual de la conversación.";
            }
        } else {
            return "No se encontró la conversación con el ID proporcionado.";
        }
    }

    public function getTranslations()
    {
        return [
            'agree' => [
                'castellano' => 'Estoy de acuerdo',
                'ingles' => 'I agree',
                'valenciano' => 'Estic d\'acord'
            ],
            'disagree' => [
                'castellano' => 'No estoy de acuerdo',
                'ingles' => 'I disagree',
                'valenciano' => 'No estic d\'acord'
            ],
            'yes' => [
                'castellano' => 'Si',
                'ingles' => 'Yes',
                'valenciano' => 'Sí'
            ],
            'no' => [
                'castellano' => 'No',
                'ingles' => 'No',
                'valenciano' => 'No'
            ],
            'castellano' => [
                'castellano' => 'Castellano',
                'ingles' => 'Spanish',
                'valenciano' => 'Castellà'
            ],
            'ingles' => [
                'castellano' => 'Inglés',
                'ingles' => 'English',
                'valenciano' => 'Anglés'
            ],
            'valenciano' => [
                'castellano' => 'Valenciano',
                'ingles' => 'Valencian',
                'valenciano' => 'Valencià'
            ],
            'no_he_entendido' => [
                'castellano' => 'Lo siento, no estoy seguro de entender. ¿Podrías reformular tu pregunta de otra manera?',
                'ingles' => "I'm sorry, I'm not sure I understand, could you rephrase your question?",
                'valenciano' => "Ho sent, no estic segur d'entendre. Podries reformular la teua pregunta d'una altra manera?"
            ],
            'error' => [
                'castellano' => 'Lo siento, estamos presentando problemas.',
                'ingles' => "Sorry, we are experiencing problems.",
                'valenciano' => "Ho sent, estem presentant problemes."
            ]
        ];
    }

    public function getLanguageSettings($conversationId)
    {
        $chatbotLog = Conversation::with('chatbotLog')->select('chatbot_log_id')->where('id', $conversationId)->firstOrFail();

        if (!$chatbotLog) {
            return [];
        }

        $chatbotId = $chatbotLog->chatbotLog->chatbot_id;
        $languages = ChatbotSetting::with('defaultTable')
            ->withTrashed()
            ->where('chatbot_id', $chatbotId)
            ->whereHas('defaultTable', function ($query) {
                $query->whereIn('name', ['castellano', 'ingles', 'valenciano']);
            })
            ->where('value', true)
            ->get();

        if ($languages->isEmpty()) {
            return [];
        }

        return $languages->pluck('defaultTable.name')->toArray();
    }

    /**
     * Obtiene el historial de una conversación.
     *
     * Este método obtiene el historial de una conversación dada su ID y el ID del chatbot asociado.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|array
     *
     * @OA\Post(
     *     path="/api/conversationHistory",
     *     tags={"conversation"},
     *     summary="Obtener el historial de una conversación.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos requeridos para obtener el historial de una conversación.",
     *         @OA\JsonContent(
     *             required={"lang", "conversation_id", "chatbot_id"},
     *             @OA\Property(property="lang", type="string", example="es"),
     *             @OA\Property(property="conversation_id", type="string", example="123456789"),
     *             @OA\Property(property="chatbot_id", type="string", example="b46c09c0-f785-4aca-a5f6-f29158ce3dac"),
     *             @OA\Property(property="agent", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historial de conversación obtenido exitosamente.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="conversation_id", type="string", example="123456789"),
     *                 @OA\Property(property="node", type="string", example="Node actual"),
     *                 @OA\Property(property="message", type="object",
     *                     @OA\Property(property="type", type="string", example="text"),
     *                     @OA\Property(property="author", type="string", example="bot"),
     *                     @OA\Property(property="data", type="object",
     *                         @OA\Property(property="text", type="string", example="Mensaje del bot"),
     *                         @OA\Property(property="action", type="string", example="faq", nullable=true),
     *                         @OA\Property(property="suggestions", type="array", @OA\Items(type="string"), nullable=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="end", type="integer", example=1, nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en los datos de entrada."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor."
     *     )
     * )
     */
    public function conversationHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lang' => ['required'],
            'conversation_id' => ['required'],
            'chatbot_id' => ['required'],
            'agent',
        ]);

        $agent = $request->input('agent');
        $conversation_id = $request->input('conversation_id');

        if ($agent) {
            $conversationLogs = ConversationLog::where('conversation_id', $conversation_id)
                ->select('message', 'type_user')
                ->get();
            $formattedLogsAgent = [];

            foreach ($conversationLogs as $log) {
                if (strpos($log->message, 'Intención:') !== false) {
                    continue;
                }
                //Se revisa con al string 'Intención:' porque es exactamente como se guarda en log message

                $formattedLogAgent = [];
                $formattedLogAgent['message'] = $log->message;
                $formattedLogAgent['type_user'] = $log->type_user;

                $formattedLogsAgent[] = $formattedLogAgent;
            }
            return $formattedLogsAgent;
        }

        $chatbot_id = $request->input('chatbot_id');
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $lang = $request->input('lang');

        $conversationLogs = ConversationLog::with('node')
            ->select('message', 'node_id', 'type_user')
            ->where('conversation_id', $conversation_id)
            ->get();

        $formattedLogs = [];

        foreach ($conversationLogs as $log) {
            if (strpos($log->message, 'Intención:') !== false) {
                continue;
            }
            //Se revisa con al string 'Intención:' porque es exactamente como se guarda en log message
            $formattedLog = [];
            $formattedLog['conversation_id'] = $conversation_id;
            if(!$log->node){
                $formattedLog['node'] = null;
            } else {
                $formattedLog['node'] = $log->node->node;
            }

            $formattedLog['message']['type'] = 'text';
            $formattedLog['message']['author'] = $log->type_user;
            $formattedLog['message']['data']['text'] = $log->message;

            if ($log->type_user === 'bot') {

                $translations = $this->getTranslations();
                $language = $this->getLanguage($chatbot_id, $lang);

                if (isset($log->node->typenode) && $log->node->typenode == 'privacy_policy') {

                    $formattedLog['message']['suggestions'] = [$translations['agree'][$language], $translations['disagree'][$language]];
                } elseif (isset($log->node->typenode) && ($log->node->typenode == 'validate_response' || $log->node->typenode == 'new_inquiry')) {

                    $formattedLog['message']['suggestions'] = [$translations['yes'][$language], $translations['no'][$language]];
                } elseif (isset($log->node->typenode) && $log->node->typenode  == 'language') {

                    $languagesSettings = $this->getLanguageSettings($conversation_id);
                    $languageResponse = array_map(function ($value) use ($translations, $language) {
                        return $translations[$value][$language];
                    }, $languagesSettings);

                    $formattedLog['message']['suggestions'] = $languageResponse;
                } elseif (isset($log->node->typenode) && $log->node->typenode  == 'faq') {

                    $dataSend = [];
                    // Verificar si nodeIntentions está cargado y no está vacío
                    if ($log->node->nodeIntentions && !$log->node->nodeIntentions->isEmpty()) {
                        foreach ($log->node->nodeIntentions as $intention) {
                            if ($intention->intention && $intention->intention->intentionLanguages) {
                                $dataSend[] = [
                                    'intention_id' => $intention->intention->id,
                                    'text' => $this->getSortedFirstLanguage($intention->intention->intentionLanguages, $language)->name,
                                    'action' => 'faq'
                                ];
                            }
                        }
                    }

                    // Verificar si nodeLanguages está cargado y no está vacío
                    if ($log->node->nodeLanguages && !$log->node->nodeLanguages->isEmpty()) {
                        $dataSend[] = [
                            'text' => $this->getSortedFirstLanguage($log->node->nodeLanguages, $language)->text,
                            'action' => 'free_question'
                        ];
                    }

                    if (is_array($dataSend) && count($dataSend) == 1 && $dataSend[0]['action'] == 'free_question') {
                        $formattedLog['message']['data']['action'] = $dataSend[0]['action'];
                        $formattedLog['message']['data']['text'] = $log->message;
                    } else {
                        $formattedLog['message']['data']['text'] = $log->message;
                        $formattedLog['message']['actions'] = $dataSend;
                    }
                } elseif (isset($log->node->typenode) && $log->node->typenode == 'end') {

                    $formattedLog['end'] = "1";
                }
            }

            $formattedLogs[] = $formattedLog;
        }

        return $formattedLogs;
    }

    public function getSortedFirstLanguage($languages, $lang)
    {
        return $languages->sortByDesc(function ($text) use ($lang) {
            return $text->language === $lang;
        })->first();
    }

    /**
     * Cierra una conversación debido a abandono.
     *
     * Este método cierra una conversación debido a abandono si cumple con ciertas condiciones.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/closeConversationAbandonment",
     *     tags={"conversation"},
     *     summary="Cerrar una conversación debido a abandono.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos requeridos para cerrar una conversación debido a abandono.",
     *         @OA\JsonContent(
     *             required={"conversation_id"},
     *             @OA\Property(property="conversation_id", type="string", example="123456789")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="La conversación fue cerrada exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="La conversación fue cerrada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en los datos de entrada."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró ninguna conversación que cumpla con las condiciones."
     *     )
     * )
     */
    public function closeConversationAbandonment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $conversation_id = $request->input('conversation_id');
        $conversation = Conversation::where('id', $conversation_id)
            ->where('conversation_status_id', 1)
            ->where('finished', 0)
            ->first();
        if ($conversation) {
            $conversation->closeAbandonment();
            return response()->json(['message' => 'La conversación fue cerrada exitosamente.'], 200);
        } else {
            return response()->json(['error' => 'No se encontró ninguna conversación que cumpla con las condiciones.'], 404);
        }
    }

    /**
     * Valida el estado de una conversación.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/validateConversationStatus",
     *     tags={"conversation"},
     *     summary="Validar el estado de una conversación",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"conversation_id"},
     *             @OA\Property(property="conversation_id", type="integer", example="1")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="La conversación está activa.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example="true"),
     *             @OA\Property(property="message", type="string", example="Conversación activa.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="La conversación no existe o está cerrada.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example="false"),
     *             @OA\Property(property="message", type="string", example="La conversación no existe.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="La conversación no existe.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example="false"),
     *             @OA\Property(property="message", type="string", example="La conversación no existe.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Ha ocurrido un error.")
     *         )
     *     )
     * )
     */
    public function validateConversationStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conversation_id' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $conversationId = $request->input('conversation_id');

        $conversation = Conversation::select('finished')->where('id', $conversationId)->first();
        $agent = Conversation::select('agent')->where('id', $conversationId)->first();

        if (!$conversation) {
            return response()->json(['success' => false, 'message' => 'La conversación no existe.'], 404);
        }

        if ($conversation->finished == 1) {
            return response()->json(['success' => false, 'message' => 'La conversación está cerrada.'], 400);
        }

        if ($agent->agent == 1) {
            return response()->json(['success' => false, 'message' => 'La conversación la tenía un agente.', 'agent' => true]);
        }

        return response()->json(['success' => true, 'message' => 'Conversación activa.'], 200);
    }

    public function desvioAgente($chatbot_id, $language, $conversation_id)
    {
        $basePath = config('app.host');
        if (!$chatbot_id) {
            return ['success' => false, 'error' => 'Es necesito un ID de chatbot para poder hacer desvío de agente'];
        }

        if (!$language) {
            return ['success' => false, 'error' => 'Es necesito el lenguaje de conversación para poder hacer desvío de agente'];
        }

        if (!$conversation_id) {
            return ['success' => false, 'error' => 'Es necesito el ID de conversación para poder hacer desvío de agente'];
        }

        $clientHistory = new Client();
        usleep(1000000);
        $responseHistory = $clientHistory->request('POST', $basePath.'/api/conversationHistory', [
            'json' => [
                'chatbot_id' => $chatbot_id,
                'lang' => $language,
                'conversation_id' => $conversation_id,
                'agent' => true
            ]
        ]);
        $bodyHistory = $responseHistory->getBody()->getContents();
        $historyDecode = json_decode($bodyHistory, true);

        $clientLogin = new Client();
        $responseLogin = $clientLogin->request('POST', $basePath.'/api/loginApi', [
            'json' => [
                'email' => 'api@api.es',
                'password' => 'Api-1234',
                "extended_token" => true,
            ]
        ]);

        $bodyLogin = $responseLogin->getBody()->getContents();
        $loginDecode = json_decode($bodyLogin, true)['token'];
        $conversationToAgent = Conversation::where('id', $conversation_id)->first();
        $conversationToAgent->token = $loginDecode;
        $conversationToAgent->save();

        try {
            $clientAgent = new Client();
            $responseAgent = $clientAgent->request('POST', $basePath.'/api/startConversation', [
                'json' => [
                    'chatbot_id' => $chatbot_id,
                    'lang' => $language,
                    'conversation_id' => $conversation_id,
                    'messages' => $historyDecode,
                    'token' => $loginDecode
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $loginDecode
                ]
            ]);

            $bodyAgent = $responseAgent->getBody()->getContents();
            $agenteData = json_decode($bodyAgent, true);
            $conversationToAgent->agent = 1;
            $conversationToAgent->save();
            return $agenteData;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $errorBody = $response->getBody()->getContents();
                $errorData = json_decode($errorBody, true);
                return $errorData;
            }
        }
    }

    public function toAgent($chatbot_id, $language, $conversation_id, $message)
    {
        $basePath = config('app.host');
        $token = null;
        if (!$chatbot_id) {
            return ['success' => false, 'error' => 'Es necesito un ID de chatbot para poder hacer desvío de agente'];
        }

        if (!$language) {
            return ['success' => false, 'error' => 'Es necesito el lenguaje de conversación para poder hacer desvío de agente'];
        }

        if (!$conversation_id) {
            return ['success' => false, 'error' => 'Es necesito el ID de conversación para poder hacer desvío de agente'];
        }

        $conversationToAgent = Conversation::where('id', $conversation_id)->first();

        if ($conversationToAgent->token) {
            $token = $conversationToAgent->token;
        } else {
            return ['success' => false, 'error' => 'Esta conversación no tiene un token para comunicarse con agentes'];
        }

        $formatedMessage = [
            'message' => $message,
            'type_user' => 'ciudadano'
        ];

        try {
            $clientAgent = new Client();
            $responseAgent = $clientAgent->request('POST', $basePath.'/api/sendToAgent', [
                'json' => [
                    'chatbot_id' => $chatbot_id,
                    'lang' => $language,
                    'conversation_id' => $conversation_id,
                    'messages' => $formatedMessage,
                    'token' => $token
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $bodyAgent = $responseAgent->getBody()->getContents();
            $agenteData = json_decode($bodyAgent, true);
            return $agenteData;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $errorBody = $response->getBody()->getContents();
                $errorData = json_decode($errorBody, true);
                return $errorData;
            }
        }
    }
}
