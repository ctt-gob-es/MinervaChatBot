<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Chatbot;
use App\Models\ChatbotLog;
use App\Models\Conversation;
use App\Models\ConversationIntention;
use App\Models\ConversationStatus;
use App\Models\TrainingStatus;
use App\Models\Intentions;
use App\Models\CityCouncils;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
/**
 * Class ChatController
 */
class DashboardController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @param Request $request
     *
     * @return Factory|Application|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {

        $conversationId = $request->get('conversationId');
        $data['conversationId'] = !empty($conversationId) ? $conversationId : 0;

        $data['users'] = User::toBase()
            ->limit(50)
            ->orderBy('name')
            ->select(['name', 'id'])
            ->pluck('name', 'id')
            ->except(getLoggedInUserId());
        return view('dashboard.index', compact('data', 'data'));
    }

    /**
     * Obtiene métricas según los parámetros proporcionados.
     *
     * Este método devuelve métricas basadas en los filtros de fecha, cliente y chatbot.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|array
     *
     * @OA\Post(
     *     path="/api/getMetricsApi",
     *     tags={"dashboard"},
     *     summary="Obtener métricas",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de entrada",
     *         @OA\JsonContent(
     *             required={"from", "to", "idCustomer", "idChatbot"},
     *             @OA\Property(property="from", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="to", type="string", format="date", example="2024-01-31"),
     *             @OA\Property(property="idCustomer", type="integer", example=1),
     *             @OA\Property(property="idChatbot", type="string", example="3j3mp10")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Métricas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="totalCiudadanos", type="integer", example=100),
     *             @OA\Property(property="tiempoConversacion", type="string", example="01:30:00"),
     *             @OA\Property(property="tasaExito", type="integer", example=80),
     *             @OA\Property(property="chatsAbandonados", type="string", example="20"),
     *             @OA\Property(property="tasaAbandono", type="integer", example=20),
     *             @OA\Property(property="porcentajeIntenciones", type="integer", example=0),
     *             @OA\Property(
     *                 property="intencionesMasUsadas",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="count", type="integer", example=1),
     *                     @OA\Property(property="intentionName", type="string", example="Intención Uno"),
     *                     @OA\Property(property="percentage", type="number", format="float", example=16.66)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="intencionesPorDia",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="date", type="string", format="date", example="2024-04-01"),
     *                     @OA\Property(property="totalIntentionsPerDay", type="integer", example=0),
     *                     @OA\Property(
     *                         property="topIntentionsPerDay",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="count", type="integer", example=1),
     *                             @OA\Property(property="intentionName", type="string", example="Intención Uno"),
     *                             @OA\Property(property="percentage", type="number", format="float", example=16.66)
     *                         )
     *                     ),
     *                     @OA\Property(property="Respuesta_negativa", type="integer", example=0),
     *                     @OA\Property(property="Respuesta_positiva", type="integer", example=0),
     *                     @OA\Property(property="Respuesta_sin_categoria", type="integer", example=0),
     *                     @OA\Property(property="Respuesta_sin_valoracion", type="integer", example=0)
     *                 )
     *             ),
     *             @OA\Property(property="totalIntentions", type="integer", example=50),
     *             @OA\Property(property="Respuesta_positiva", type="integer", example=30),
     *             @OA\Property(property="Respuesta_negativa", type="integer", example=10),
     *             @OA\Property(property="Respuesta_sin_valoracion", type="integer", example=5),
     *             @OA\Property(property="Respuesta_sin_categoria", type="integer", example=5)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parámetros faltantes o no válidos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Los parámetros enviados son insuficientes o no válidos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Ocurrió un error interno en el servidor")
     *         )
     *     )
     * )
     */
    public function getMetrics(Request $request)
    {
        try {
            $request->validate([
                'from' => 'required',
                'to' => 'required',
                'idCustomer' => 'required|exists:city_councils,id',
                'idChatbot' => 'required|exists:chatbots,id',
            ], [
                'idCustomer.exists' => 'El ID de cliente no es válido.',
                'idChatbot.exists' => 'El ID del chatbot no es válido.'
            ]);

            $data = $request->all();
            $from = $request->input("from");
            $to = $request->input("to");

            if (!$this->isValidDate($from) || !$this->isValidDate($to)) {
                return response()->json(['error' => 'Los campos from y to deben ser fechas válidas en formato yyyy-mm-dd.'], 404);
            }

            $idCustomer = $request->input("idCustomer");
            $idChatbot = $request->input("idChatbot");

            $startDate = $from ? Carbon::parse($from)->setTimezone('Europe/Madrid')->startOfDay() : null;
            $endDate = $to ? Carbon::parse($to)->setTimezone('Europe/Madrid')->endOfDay() : null;

            if ($idCustomer !== null && $idChatbot !== null) {
                $metrics = $this->chatbotMetrics($idCustomer, $idChatbot, $startDate, $endDate);
            }
            // elseif ($idCustomer === null && $idChatbot === null) {
            //     $metrics = $this->generalMetrics($startDate, $endDate);
            // } elseif ($idCustomer !== null && $idChatbot === null) {
            //     $metrics = $this->customerMetrics($idCustomer, $startDate, $endDate);
            // }
            else {
                Log::error('error');
            }
            return response()->json($metrics, 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Throwable $th) {
            return ['error' => $th->getMessage()];
        }
    }


    /**
 * Obtiene los chatbots asociados a un cliente específico.
 *
 * Este método devuelve una lista de chatbots que están asociados a un cliente específico.
 * Si se proporciona un ID de cliente válido, devuelve los chatbots asociados a ese cliente.
 * Si no se proporciona un ID de cliente válido o se proporciona 'null', devuelve todos los chatbots.
 *
 * @param  int|string  $id  El ID del cliente (puede ser 'null' como string)
 * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Http\JsonResponse
 *
 * @OA\Get(
 *     path="/api/getChatbotsPerCustomerApi/{id}",
 *     tags={"dashboard"},
 *     summary="Obtener chatbots por cliente",
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID del cliente",
 *
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Chatbots obtenidos exitosamente",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="string", example="3x4mpl3"),
 *                 @OA\Property(property="name", type="string", example="Chatbot 1")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Con esos parametros se ha encontrado cliente",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="El cliente especificado no fue encontrado.")
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
    public function getChatbotsPerCustomer($id)
    {
        try{
            if (!isset($id) || !is_numeric($id)) {
                return response()->json(['error' => 'El ID del cliente es inválido.'], 400);
            }

            $cityCouncil = CityCouncils::where('id',$id)->exists();
            if (!$cityCouncil) {
                return response()->json(['error' => 'El cliente especificado no fue encontrado.'], 404);
            }

            $chatbots = Chatbot::where('city_councils_id', $id)
                ->select('id', 'name')
                ->get();
            return $chatbots;

        } catch (\Throwable $th) {
            return ['error' => $th->getMessage(), 'trace' => $th->getTraceAsString()];
        }
    }

    public function getCustomers()
    {
        $user = User::find(auth()->id());
        $roles = $user->getRoleNames();
        if ($roles[0] !== 'SuperAdmin') {
            return \App\Models\ManageClient::join('users', 'users.id', 'manage_clients.user_id')
                ->join('city_councils', 'city_councils.id', 'manage_clients.client_id')
                ->select('city_councils.*')->where('manage_clients.user_id', auth()->id())->get();
        } else {
            return \App\Models\CityCouncils::get();
        }
    }

    public function chatbotMetrics($idCustomer, $idChatbot, $from, $to){
        try{

            $totalConversations = 0;
            $averageConversationTimeSeconds = 0;
            $abandonedChats = 0;
            $totalAbandonedPercentage = 0;
            $percentageTopIntentionsWithName = null;

            $finishedStatusId = ConversationStatus::where('name', 'Finalizada')->value('id');
            $inactivityStatusId = ConversationStatus::where('name', 'Inactividad')->value('id');
            $abandonedStatusId = ConversationStatus::where('name', 'Abandono')->value('id');

            $chatbotLogIds = ChatbotLog::where('chatbot_id', $idChatbot)->pluck('id');

            $metricsQuery = Conversation::whereIn('chatbot_log_id', $chatbotLogIds);

            if ($from && $to) {
                $fromDateTime = $from->format('Y-m-d H:i:s');
                $toDateTime = $to->format('Y-m-d H:i:s');
                $metricsQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
            }

            $metrics = $metricsQuery->selectRaw('
                COUNT(*) AS total_conversations,
                AVG(CASE
                    WHEN conversation_status_id = ? THEN TIMESTAMPDIFF(SECOND, created_at, updated_at)
                    WHEN conversation_status_id = ? THEN TIMESTAMPDIFF(SECOND, created_at, updated_at)
                    WHEN conversation_status_id = ? THEN TIMESTAMPDIFF(SECOND, created_at, updated_at)
                END) AS average_conversation_time,
                SUM(CASE
                    WHEN conversation_status_id = ? OR conversation_status_id = ? THEN 1 ELSE 0
                END) AS abandoned_chats
                ', [$finishedStatusId, $inactivityStatusId, $abandonedStatusId, $inactivityStatusId, $abandonedStatusId])
            ->first();

            $detailByDay = $this->detailByDay($idCustomer, $idChatbot, $from, $to);

            $conversationsId = Conversation::whereIn('chatbot_log_id', $chatbotLogIds)->pluck('id');

            $excludedIntentions = ['FORMULARIO_TERMINADO', 'desvio_agente', 'mood_great', 'bot_challenge', 'cancelar',
            'no_le_he_entendido', 'greet', 'affirm', 'goodbye', 'deny', 'mood_unhappy'];

            $intentions = ConversationIntention::whereIn('conversation_id', $conversationsId)
            ->whereBetween('created_at', [$fromDateTime, $toDateTime])
            ->whereHas('intention', function($query) use ($excludedIntentions) {
                $query->whereNotIn('name', $excludedIntentions);
            })
            ->get();

            $totalIntentions = $intentions->count();

            $intentionsCount = $intentions->filter(function ($item) {
                return $item->intention_id !== null;
            })->groupBy('intention_id')->map->count();

            $sortedIntentionsCount = $intentionsCount->sortByDesc(function ($count) {
                return $count;
            });

            $topIntentions = $sortedIntentionsCount->take($intentionsCount->count());

            $intentionNames = Intentions::whereIn('id', $topIntentions->keys())->pluck('name', 'id');

            $percentageTopIntentionsWithName = $topIntentions->map(function ($count, $intentionId) use ($intentionNames, $totalIntentions) {
                $percentage = ($count / $totalIntentions) * 100;
                return [
                'intentionName' => $intentionNames[$intentionId],
                'count' => $count,
                'percentage' => $percentage,
                'percentageOfTotal' => $percentage
            ];
        });

        $countNullIntentions = $intentions->where('type', 'abierta')->count();

        $successStatus = TrainingStatus::where('name', 'Positiva')->value('id');
        $negativeStatus = TrainingStatus::where('name', 'Negativo')->value('id');
        $withoutFeedStatus = TrainingStatus::where('name', 'Sin Valoración')->value('id');
        $withoutCategoryStatus = TrainingStatus::where('name', 'Sin Categoría')->value('id');

        $countSuccessIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $successStatus)->count();
        $countNegativeIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $negativeStatus)->count();
        $countWithoutFeedIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $withoutFeedStatus)->count();
        $countWithoutCategoryIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $withoutCategoryStatus)->count();

        $successRate = $countNullIntentions > 0 && $countSuccessIntentions > 0 ? ($countSuccessIntentions / $countNullIntentions) * 100 : 0;

        $totalConversations = $metrics->total_conversations;
        $averageConversationTimeSeconds = $metrics->average_conversation_time;
        $hours = $averageConversationTimeSeconds > 0 ? floor($averageConversationTimeSeconds / 3600) : 00;
        $minutes = $averageConversationTimeSeconds > 0 ? floor(($averageConversationTimeSeconds % 3600) / 60) : 00;
        $seconds = $averageConversationTimeSeconds > 0 ? $averageConversationTimeSeconds % 60 : 00;

        $averageConversationTimeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        $abandonedChats = $metrics->abandoned_chats > 0 ? $metrics->abandoned_chats : 0;

        $totalAbandonedPercentage = $abandonedChats > 0 && $totalConversations > 0 ? ($abandonedChats / $totalConversations) * 100 : 0;

        return [
            'totalCiudadanos' => $totalConversations,
            'tiempoConversacion' => $averageConversationTimeFormatted,
            'tasaExito' => $successRate,
            'chatsAbandonados' => $abandonedChats,
            'tasaAbandono' => $totalAbandonedPercentage,
            'porcentajeIntenciones' => 0,
            'intencionesMasUsadas' => $percentageTopIntentionsWithName,
            'intencionesPorDia' =>  $detailByDay,
            'totalIntentions' => $totalIntentions,
            'Respuesta_positiva' => $countSuccessIntentions,
            'Respuesta_negativa' => $countNegativeIntentions,
            'Respuesta_sin_valoracion' => $countWithoutFeedIntentions,
            'Respuesta_sin_categoria' => $countWithoutCategoryIntentions
        ];
        } catch (\Throwable $th) {
            Log::error('error', ['error' => $th->getMessage(), 'line' => $th->getLine(), 'info'=>$th]);
        }
    }

    public function generalMetrics($from, $to)
    {
        $totalConversations = 0;
        $abandonedChats = 0;
        $totalAbandonedPercentage = 0;
        $averageConversationTimeSeconds = 0;

        $fromDateTime = $from->format('Y-m-d H:i:s');
        $toDateTime = $to->format('Y-m-d H:i:s');

        $generalMetrics = Conversation::whereBetween('created_at', [$fromDateTime, $toDateTime])
        ->selectRaw('
        COUNT(*) AS total_conversations,
            AVG(CASE WHEN finished = 1 THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) END) AS average_conversation_time,
            SUM(CASE WHEN finished = -1 THEN 1 ELSE 0 END) AS abandoned_chats
        ')
        ->first();

        $totalConversations = $generalMetrics->total_conversations;
        $averageConversationTimeSeconds = $generalMetrics->average_conversation_time;

        $hours = $averageConversationTimeSeconds > 0 ? floor($averageConversationTimeSeconds / 3600) : 00;
        $minutes = $averageConversationTimeSeconds > 0 ? floor(($averageConversationTimeSeconds % 3600) / 60) : 00;
        $seconds = $averageConversationTimeSeconds > 0 ? $averageConversationTimeSeconds % 60 : 00;

        $averageConversationTimeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        $abandonedChats = $generalMetrics->abandoned_chats;

        if ($abandonedChats != 0 && $totalConversations != 0) {
            $totalAbandonedPercentage = ($abandonedChats / $totalConversations) * 100;
        } else {
            $totalAbandonedPercentage = 0;
        }

        return [
            'totalCiudadanos' => $totalConversations,
            'tiempoConversacion' => $averageConversationTimeFormatted,
            'tasaExito' => 0,
            'chatsAbandonados' => $abandonedChats,
            'tasaAbandono' => $totalAbandonedPercentage,
            'porcentajeIntenciones' => 0,
        ];
    }

    public function customerMetrics($idCustomer, $from, $to){

        $chatbotIds = Chatbot::where('city_councils_id', $idCustomer)->pluck('id');

        $totalConversations = 0;
        $totalConversationTime = 0;
        $totalAbandonedChats = 0;
        $averageConversationTimeSeconds = 0;
        $percentageTopIntentionsWithName = null;
        $allIntentions = collect();

        foreach ($chatbotIds as $chatbotId) {
            $chatbotLogIds = ChatbotLog::where('chatbot_id', $chatbotId)->pluck('id');
            $metricsQuery = Conversation::whereIn('chatbot_log_id', $chatbotLogIds);

            if ($from && $to) {
                $fromDateTime = $from->format('Y-m-d H:i:s');
                $toDateTime = $to->format('Y-m-d H:i:s');
                $metricsQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
            }

            $metrics = $metricsQuery->selectRaw('
                COUNT(*) AS total_conversations,
                AVG(CASE WHEN finished = 1 OR finished = -1 THEN TIMESTAMPDIFF(SECOND, created_at, updated_at) END) AS average_conversation_time,
                SUM(CASE WHEN finished = -1 THEN 1 ELSE 0 END) AS abandoned_chats
                ')
            ->first();

            $totalConversations += $metrics->total_conversations;
            $totalConversationTime += $metrics->average_conversation_time;
            $totalAbandonedChats += $metrics->abandoned_chats;

            $conversationsQuery = Conversation::whereIn('chatbot_log_id', $chatbotLogIds);

            if ($from && $to) {
                $conversationsQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
            }

            $conversationsId = $conversationsQuery->pluck('id');

            $intentions = ConversationIntention::whereIn('conversation_id', $conversationsId)->get();
            $allIntentions = $allIntentions->concat($intentions);
        }

        $totalIntentions = $allIntentions->count();

        $intentionsCount = $allIntentions->filter(function ($item) {
            return $item->intention_id !== null;
        })->groupBy('intention_id')->map->count();

        $sortedIntentionsCount = $intentionsCount->sortByDesc(function ($count) {
            return $count;
        });

        $topIntentions = $sortedIntentionsCount->take(3);

        $intentionNames = Intentions::whereIn('id', $topIntentions->keys())->pluck('name', 'id');

        $percentageTopIntentionsWithName = $topIntentions->map(function ($count, $intentionId) use ($intentionNames, $totalIntentions) {
            $percentage = ($count / $totalIntentions) * 100;
            return [
            'intentionName' => $intentionNames[$intentionId],
            'count' => $count,
            'percentage' => $percentage,
            'percentageOfTotal' => $percentage
            ];
        });

        $countNullIntentions = $allIntentions->whereNull('intention_id')->count();
        $countSuccessIntentions = $allIntentions->where('intention_id', null)->where('result', 1)->count();
        $successRate = $countNullIntentions > 0 && $countSuccessIntentions > 0 ? ($countSuccessIntentions / $countNullIntentions) * 100 : 0;

        $averageConversationTimeSeconds = $totalConversations > 0 ? ($totalConversationTime / count($chatbotIds)) : 0;

        $hours = $averageConversationTimeSeconds > 0 ? floor($averageConversationTimeSeconds / 3600) : 00;
        $minutes = $averageConversationTimeSeconds > 0 ? floor(($averageConversationTimeSeconds % 3600) / 60) : 00;
        $seconds = $averageConversationTimeSeconds > 0 ? $averageConversationTimeSeconds % 60 : 00;

        $averageConversationTimeFormatted = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);

        $totalAbandonedPercentage = $totalConversations > 0 ? ($totalAbandonedChats / $totalConversations) * 100 : 0;

        return [
            'totalCiudadanos' => $totalConversations,
            'tiempoConversacion' => $averageConversationTimeFormatted,
            'tasaExito' => $successRate,
            'chatsAbandonados' => $totalAbandonedChats,
            'tasaAbandono' => $totalAbandonedPercentage,
            'porcentajeIntenciones' => 0,
            'intencionesMasUsadas' => $percentageTopIntentionsWithName
        ];
    }

    private function detailByDay($idCustomer, $idChatbot, $from, $to){
        $metricsByDay = [];

        $currentDate = $from->copy();
        while ($currentDate <= $to) {
            $topIntentionsArray = [];
            $totalIntentions = 0;
            $topIntentions = [];
            $fromDateTime = $currentDate->format('Y-m-d 00:00:00');
            $toDateTime = $currentDate->format('Y-m-d 23:59:59');

            $chatbotLogIds = ChatbotLog::where('chatbot_id', $idChatbot)
            ->pluck('id');

            $conversationsId = Conversation::whereIn('chatbot_log_id', $chatbotLogIds)->pluck('id');

            $excludedIntentions = ['FORMULARIO_TERMINADO', 'desvio_agente', 'mood_great', 'bot_challenge', 'cancelar',
            'no_le_he_entendido', 'greet', 'affirm', 'goodbye', 'deny', 'mood_unhappy'];

            $intentions = ConversationIntention::whereIn('conversation_id', $conversationsId)
            ->whereBetween('created_at', [$fromDateTime, $toDateTime])
            ->whereHas('intention', function($query) use ($excludedIntentions) {
                $query->whereNotIn('name', $excludedIntentions);
            })
            ->get();

            $totalIntentions = $intentions->count();

            $intentionsCount = $intentions->filter(function ($item) {
                return $item->intention_id !== null;
            })->groupBy('intention_id')->map->count();

            $sortedIntentionsCount = $intentionsCount->sortByDesc(function ($count) {
                return $count;
            });

            $topIntentions = $sortedIntentionsCount->take($intentionsCount->count())->each(function ($count, $intentionId) use ($totalIntentions, &$topIntentionsArray) {
                $topIntentionsArray[] = [
                    'intentionName' => Intentions::find($intentionId)->name,
                    'count' => $count,
                    'percentage' => ($count / $totalIntentions) * 100,
                    'percentageOfTotal' => ($count / $totalIntentions) * 100
                ];
            });

            $countNullIntentions = $intentions->where('type', 'abierta')->count();

            $successStatus = TrainingStatus::where('name', 'Positiva')->value('id');
            $negativeStatus = TrainingStatus::where('name', 'Negativo')->value('id');
            $withoutFeedStatus = TrainingStatus::where('name', 'Sin Valoración')->value('id');
            $withoutCategoryStatus = TrainingStatus::where('name', 'Sin Categoría')->value('id');

            $countSuccessIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $successStatus)->count();
            $countNegativeIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $negativeStatus)->count();
            $countWithoutFeedIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $withoutFeedStatus)->count();
            $countWithoutCategoryIntentions = $intentions->where('type', 'abierta')->where('training_status_id', $withoutCategoryStatus)->count();

            $successRate = $countNullIntentions > 0 && $countSuccessIntentions > 0 ? ($countSuccessIntentions / $countNullIntentions) * 100 : 0;

            $metricsByDay[] = [
                'date' => $currentDate->format('Y-m-d'),
                'totalIntentionsPerDay' => $totalIntentions,
                'topIntentionsPerDay' => $topIntentionsArray,
                'Respuesta_positiva' => $countSuccessIntentions,
                'Respuesta_negativa' => $countNegativeIntentions,
                'Respuesta_sin_valoracion' => $countWithoutFeedIntentions,
                'Respuesta_sin_categoria' => $countWithoutCategoryIntentions
            ];

            $currentDate->addDay();
        }
        return $metricsByDay;
    }

    public function getInitialRedirectPath()
    {
        $authId = Auth::id();

        $usersPer = DB::table('role_has_permissions')
                ->join('model_has_roles as mhr', 'mhr.role_id', '=', 'role_has_permissions.role_id')
                ->join('users as u', 'u.id', '=', 'mhr.model_id')
                ->join('permissions  as p', 'p.id', '=', 'role_has_permissions.permission_id')
                ->selectRaw("u.id")
                ->whereRaw("p.name='manage_dashboard' AND u.id=".Auth::id())
                ->exists();

        if(!$usersPer){
            $authUser = Auth::user();

            $userPermissions = $authUser->getAllPermissions()->pluck('name')->toArray();

            $permissionRoutes = [
                'manage_dashboard' => '/dashboard',
                'manage_chatbots' => '/chatbots',
                'manage_clients' => '/customers',
                'manage_training' => '/supervised_training',
                'manage_training_manual' => '/supervised_manual',
                'manage_conversations' => '/conversations',
                'manage_settings' => '/settings',
                'manage_users' => '/users',
                'manage_roles' => '/roles',
            ];

            foreach ($permissionRoutes as $permission => $route) {
                if (in_array($permission, $userPermissions)) {
                    return $route;
                }
            }

            return '/settings';
        } else {
            return 'permission';
        }
    }

    private function isValidDate($date)
    {
        return (bool)strtotime($date);
    }
}
