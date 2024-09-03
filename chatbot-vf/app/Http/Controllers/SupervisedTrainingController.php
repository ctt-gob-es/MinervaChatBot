<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Question;
use App\Models\Intentions;
use App\Models\Chatbot;
use Illuminate\Http\Request;
use App\Models\ConversationIntention;
use App\Models\QuestionLanguage;
use Illuminate\Support\Facades\Log;

class SupervisedTrainingController extends Controller
{
    /**
     * Obtiene preguntas abiertas para entrenamiento supervisado.
     *
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/resourceSupervisedTrainingApi",
     *     tags={"supervisedTraining"},
     *     summary="Obtiene el listado de preguntas en entrenamiento supervisado.",
     *     @OA\Parameter(
     *         name="Q",
     *         in="query",
     *         description="Tipo de consulta",
     *         required=true,
     *         @OA\Schema(
     *             type="integer",
     *             enum={0}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="chatbot_id",
     *         in="query",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Fecha de inicio para el filtro (opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha de fin para el filtro (opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Conversaciones abiertas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="question", type="string"),
     *                 @OA\Property(property="answer", type="string"),
     *                 @OA\Property(property="language", type="string"),
     *                 @OA\Property(property="intention_id", type="integer"),
     *                 @OA\Property(property="intention", type="string"),
     *                 @OA\Property(property="subjects_id", type="integer"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="state", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function index()
    {
        $chatbotExists = Chatbot::where('id', $_GET['chatbot_id'])->exists();
        if(!$chatbotExists){
            return response()->json(['success'=>false, 'message'=>'El id de chatbot proporcionado no es valido.']);
        }

        if($_GET['Q'] != 0 && $_GET['Q'] != 1 && $_GET['Q'] != 2){
            return response()->json(['success'=>false, 'message'=>'Por favor, el parametro Q debe ser 0 o 1. 0 para listar Con intención y 1 Sin intención.']);
        }

        switch ($_GET['Q']) {
            case 0:
                $query = ConversationIntention::join('conversations', 'conversations.id', 'conversation_intentions.conversation_id')
                    ->join('chatbot_logs', 'chatbot_logs.id', 'conversations.chatbot_log_id')
                    ->leftJoin('intentions', 'intentions.id', 'conversation_intentions.intention_id')
                    ->join('training_status', 'training_status.id', 'conversation_intentions.training_status_id')
                    ->where([['chatbot_logs.chatbot_id', $_GET['chatbot_id']], ['conversation_intentions.type', 'abierta'], ['conversation_intentions.manual_rating', null]])
                    ->where(function ($query) {
                        $query->whereNull('intentions.name')
                            ->orWhereNotIn('intentions.name', [
                                'FORMULARIO_TERMINADO', 'desvio_agente', 'mood_great', 'bot_challenge', 'cancelar',
                                'no_le_he_entendido', 'greet', 'affirm', 'goodbye', 'deny', 'mood_unhappy'
                            ]);
                    })
                    ->select(
                        'conversation_intentions.id',
                        'conversation_intentions.question',
                        'conversation_intentions.answer',
                        'conversations.language',
                        'intentions.id as intention_id',
                        'intentions.name as intention',
                        'intentions.subjects_id',
                        'conversation_intentions.created_at',
                        'training_status.name as state'
                    );

                if (isset($_GET['from']) && isset($_GET['to']) && $_GET['from'] !== 'null' && $_GET['to'] !== 'null') {
                    $to = date('Y-m-d', strtotime($_GET['to'] . ' +1 day'));
                    $query->whereBetween('conversation_intentions.created_at', [$_GET['from'], $to]);
                }
                $data = $query->orderByDesc('conversation_intentions.created_at')->get();
                break;
            case 1:
                $data = Subject::where('chatbot_id', $_GET['chatbot_id'])->get();
                break;
            case 2:
                $data = Intentions::where('chatbot_id', $_GET['chatbot_id'])->get();
                break;
        }
        return response()->json($data);
    }

    /**
     * Almacena una nueva pregunta en la base de datos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/setRatingApi",
     *     tags={"supervisedTraining"},
     *     summary="Almacena una nueva pregunta para entrenamiento supervisado",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la pregunta a almacenar",
     *         @OA\JsonContent(
     *             required={"intention_id", "question"},
     *             @OA\Property(property="intention_id", type="integer", description="ID de la intención asociada a la pregunta"),
     *             @OA\Property(property="question", type="object", description="Pregunta en varios idiomas",
     *                 @OA\AdditionalProperties(
     *                     type="object",
     *                     @OA\Property(property="value", type="string", description="Valor de la pregunta en un idioma específico")
     *                 )
     *             ),
     *             @OA\Property(property="id", type="integer", description="ID de la intención de conversación a actualizar")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Se ha almacenado la pregunta correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true, description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", example="Rating guardado", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false, description="Indica si la operación no fue exitosa"),
     *             @OA\Property(property="message", type="string", example="Error al guardar el rating", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {

        try {
            $validated = $request->validate([
                'id' => 'required|integer',
                'intention_id' => 'required|integer',
                'question' => 'required'
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->messages();
            $errorMessage = '';

            foreach ($errors as $field => $messagesError) {
                $errorMessage .= "El campo '$field' es obligatorio. ";
            }

            return response()->json(['success' => false, 'error' => $errorMessage], 422);
        }

        $intentionExists = Intentions::where('id', $request->intention_id)->exists();
        if(!$intentionExists){
            return response()->json(['success' => false, 'message' => 'El id de intención proporcionado no es valido.']);
        }

        $q = Question::create([
            'intentions_id' => $request->intention_id
        ]);

        if (is_string($request->question)) {
            $question = json_decode($request->question, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Error al decodificar JSON', 'success' => false
                ], 400);
            }
        } else {
            $question = $request->question;
        }

        foreach ($question as $lang => $value) {
            QuestionLanguage::create([
                'question' => $value['value'],
                'language' => $lang,
                'question_id' => $q->id
            ]);
        }

        $ConversationIntention = ConversationIntention::where('id', $request->id)->exists();
        if(!$ConversationIntention){
            return response()->json(['success' => false, 'message' => 'El id proporcionado para entrenamiento supervisado no es valido.']);
        }

        ConversationIntention::where('id', $request->id)->update([
            'manual_rating' => 'Descartada'
        ]);
        return response()->json(['success' => true, 'message' => 'Rating guardado'], 200);
    }


    /**
     * Actualiza el rating de varias preguntas.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/descartRatingApi",
     *     tags={"supervisedTraining"},
     *     summary="Actualiza el rating de varias preguntas",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de las preguntas a actualizar",
     *         @OA\JsonContent(
     *             required={"data"},
     *             @OA\Property(property="data", type="array", description="Arreglo de objetos que contienen el ID de las preguntas a actualizar",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", description="ID de la conversación a actualizar")
     *                 )
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Se han actualizado los ratings de las preguntas correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true, description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", example="Descartada", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error."
     *     )
     * )
     */
    public function update(Request $request)
    {
        foreach ($request->data as $rt) {
            ConversationIntention::where('id', $rt['id'])->update([
                'manual_rating' => 'Descartada'
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Descartada'], 200);
    }
}
