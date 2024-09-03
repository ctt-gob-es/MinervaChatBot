<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Day;
use App\Models\Node;
use App\Models\Answers;
use App\Models\Chatbot;
use App\Models\Subject;
use App\Models\Question;
use App\Models\Schedule;
use App\Models\TimeSlot;
use App\Models\ChatbotLog;
use App\Models\Intentions;
use App\Models\ChatbotPort;
use App\Models\DayTimeSlot;
use App\Models\CityCouncils;
use App\Models\NodeLanguage;
use Illuminate\Http\Request;
use App\Models\NodeIntention;
use App\Models\ChatbotSetting;
use App\Models\ConversationStatus;
use App\Models\Conversation;
use App\Models\DefaultSetting;
use App\Models\NodeTransition;
use App\Models\AnswersLanguage;
use App\Models\QuestionLanguage;
use App\Models\IntentionLanguage;
use App\Models\ConceptLanguage;
use App\Models\Concept;
use App\Models\IntentionsConcept;
use App\Models\Lists;
use App\Models\ListTerm;
use App\Models\TermsLanguage;
use App\Models\ConceptList;
use App\Models\User;
use Aunnait\Rasalicante\RasaComm;
use Illuminate\Support\Facades\DB;
use App\Models\ChatbotModification;
use App\Models\ScheduleDayTimeSlot;
use Illuminate\Support\Facades\Log;
use Aunnait\Rasalicante\RasaBotControl;
use Illuminate\Support\Facades\Validator;
use App\Jobs\TrainChatbot;

class ChatbotsController extends Controller
{
    /**
     * Lista los chatbots según los parámetros proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|null $idCustomer (Opcional) ID del cliente para filtrar los chatbots asociados.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getChatbotsApi",
     *     tags={"chatbots"},
     *     summary="Lista los chatbots",
     *     @OA\Parameter(
     *         name="idCustomer",
     *         in="query",
     *         description="ID del cliente para filtrar los chatbots asociados (opcional)"
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Fecha de inicio para filtrar los chatbots por fecha de creación (opcional)"
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha de fin para filtrar los chatbots por fecha de creación (opcional)"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Se devuelve una lista de chatbots según los parámetros proporcionados."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un problema al listar los chatbots."
     *     )
     * )
     */
    public function index(Request $request, $idCustomer = null)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un problema al listar los chatbots.'], 404);

        try {
            $query = Chatbot::query();

            if (getLoggedInUserRole() != 'SuperAdmin' && $idCustomer == null) {
                $errorMessage = response()->json(['success' => false, 'message' => 'Ingrese un id de cliente.'], 404);
            }

            if ($idCustomer !== null) {
                $query->where('city_councils_id', $idCustomer);
            }

            if ($request->has('from') && $request->has('to')) {
                $from = $request->input('from');
                $to = $request->input('to');

                $query->whereBetween('created_at', [$from, $to]);
            }

            $checkLanguages = [];
            $validLanguages = ['castellano', 'ingles', 'valenciano'];
            $additionalSettings = ['idioma_principal'];

            $chatbots = $query->with([
                'creator',
                'cityCouncil',
                'modifications' => function ($query) {
                    $query->select('id', 'action', 'chatbot_id', 'user_id', 'created_at');
                },
                'modifications.user',
                'settings' => function ($query) use ($validLanguages, $additionalSettings) {
                    $query->where(function ($query) use ($validLanguages) {
                        $query->whereHas('defaultTable', function ($query) use ($validLanguages) {
                            $query->whereIn('name', $validLanguages);
                        })->where('value', 1);
                    })->orWhere(function ($query) use ($additionalSettings) {
                        $query->whereHas('defaultTable', function ($query) use ($additionalSettings) {
                            $query->whereIn('name', $additionalSettings);
                        });
                    })->whereNull('deleted_at');
                },
                'settings.defaultTable'
            ])->orderBy('created_at', 'desc')->get();

            $chatbots->each(function ($chatbot) {
                $chatbot->messageAlert = [];
                $activeLanguages = [];
                $chatbot->buildAlert = false;

                $chatbot->settings->transform(function ($setting) use (&$activeLanguages) {
                    $setting->name_setting = optional($setting->defaultTable)->name;
                    $setting->type_setting = optional($setting->defaultTable)->type;
                    if ($setting->type_setting == 'idioma' && $setting->value == '1') {
                        $activeLanguages[] = $setting->name_setting;
                    }
                    return $setting;
                });

                $drawflow = json_decode($chatbot->information, true);
                $messages = $this->extractMessagesFromDrawflow($drawflow);
                foreach ($activeLanguages as $activeLanguage) {
                    $found = false;
                    foreach ($messages as $message) {
                        $languagesInMessage = array_column($message, 'language');
                        if (in_array($activeLanguage, $languagesInMessage)) {
                            $found = true;
                        }

                        if (!$found) {
                            $chatbot->buildAlert = true;
                            break;
                        }
                    }

                    if (!$found) {
                        break;
                    }
                }

                $intentionIds = Intentions::where('chatbot_id', $chatbot->id)
                    ->pluck('id');

                foreach ($intentionIds as $intentionId) {
                    $intentionLanguages = IntentionLanguage::where('intention_id', $intentionId)
                        ->get();
                    foreach ($activeLanguages as $language) {
                        $exists = $intentionLanguages->contains('language', $language);

                        if (!$exists) {
                            $errorIntention = Intentions::where('id', $intentionId)->value('name');
                            if ($language == 'ingles') {
                                $correctLanguage = 'inglés';
                            } else {
                                $correctLanguage = $language;
                            }
                            $message = 'Intención: ' . strtoupper($errorIntention) . '. No está configurada en ' . strtoupper($correctLanguage) . '. Por favor, revisa en detalle el conocimiento del chatbot.';
                            $chatbot->messageAlert = array_merge($chatbot->messageAlert, [$message]);
                        }
                    }
                    $answersId = Answers::where('intentions_id', $intentionId)->pluck('id');
                    foreach ($answersId as $answerId) {
                        $answerLanguages = AnswersLanguage::where('answers_id', $answerId)
                            ->get();
                        foreach ($activeLanguages as $language) {
                            $answerExist = $answerLanguages->contains('language', $language);

                            if (!$answerExist) {
                                $errorIntention = Intentions::where('id', $intentionId)->value('name');
                                if ($language == 'ingles') {
                                    $correctLanguage = 'INGLÉS';
                                } else {
                                    $correctLanguage = $language;
                                }
                                $message = 'Intención: ' . strtoupper($errorIntention) . '. No tiene configuradas todas sus respuestas en ' . strtoupper($correctLanguage) . '. Por favor, revisa en detalle.';
                                $chatbot->messageAlert = array_merge($chatbot->messageAlert, [$message]);
                            }
                        }
                    }

                    $questionsId = Question::where('intentions_id', $intentionId)->pluck('id');
                    foreach ($questionsId as $questionId) {
                        $questionLanguages = QuestionLanguage::where('question_id', $questionId)
                            ->get();

                        foreach ($activeLanguages as $language) {
                            $questionExist = $questionLanguages->contains('language', $language);
                            if (!$questionExist) {
                                $errorIntention = Intentions::where('id', $intentionId)->value('name');
                                if ($language == 'ingles') {
                                    $correctLanguage = 'INGLÉS';
                                } else {
                                    $correctLanguage = $language;
                                }
                                $message = 'Intención: ' . strtoupper($errorIntention) . '. No tiene configuradas todas sus preguntas en ' . strtoupper($correctLanguage) . '. Por favor, revisa en detalle.';
                                $chatbot->messageAlert = array_merge($chatbot->messageAlert, [$message]);
                            }
                        }
                    }


                    $conceptsId = IntentionsConcept::where('intention_id', $intentionId)->pluck('concept_id');
                    foreach ($conceptsId as $conceptId) {
                        $conceptLanguage = ConceptLanguage::where('concept_id', $conceptId)
                            ->get();

                        foreach ($activeLanguages as $language) {
                            $conceptExist = $conceptLanguage->contains('language', $language);
                            if (!$conceptExist) {
                                if ($language == 'ingles') {
                                    $correctLanguage = 'INGLÉS';
                                } else {
                                    $correctLanguage = $language;
                                }
                                $errorIntention = Intentions::where('id', $intentionId)->value('name');
                                $errorConcept = Concept::where('id', $conceptId)->value('name');
                                $message = 'Contexto: ' . strtoupper($errorConcept) . '. El contexto asociado a la intención ' . strtoupper($errorIntention) . ' no está configurado en ' . strtoupper($correctLanguage);
                                $chatbot->messageAlert = array_merge($chatbot->messageAlert, [$message]);
                            }
                        }
                        $conceptLists = ConceptList::where('concept_id', $conceptId)
                            ->pluck('list_id');
                        $lists = Lists::whereIn('id', $conceptLists)->pluck('id');
                        foreach ($lists as $list) {
                            $listTerms = ListTerm::where('list_id', $list)->pluck('id');
                            foreach ($listTerms as $term) {
                                $termLang = TermsLanguage::where('list_term_id', $term)
                                    ->get();

                                foreach ($activeLanguages as $language) {
                                    $termLangExist = $termLang->contains('language', $language);
                                    if (!$termLangExist) {
                                        if ($language == 'ingles') {
                                            $correctLanguage = 'INGLÉS';
                                        } else {
                                            $correctLanguage = $language;
                                        }
                                        $errorIntention = Intentions::where('id', $intentionId)->value('name');
                                        $errorConcept = Concept::where('id', $conceptId)->value('name');
                                        $errorList = Lists::where('id', $list)->value('name');
                                        $message = 'La lista ' . strtoupper($errorList) . ' no tiene configurados los terminos en ' . strtoupper($correctLanguage) . ' Esa lista está asociada a la intención ' . strtoupper($errorIntention) . ', a través del contexto ' . strtoupper($errorConcept) . '.';
                                        $chatbot->messageAlert = array_merge($chatbot->messageAlert, [$message]);
                                    }
                                }
                            }
                        }
                    }
                }
            });

            return response()->json(['success' => true, 'data' => $chatbots], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Crea un nuevo chatbot en la base de datos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/saveChatbotApi",
     *     tags={"chatbots"},
     *     summary="Crea un nuevo chatbot",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del chatbot a crear",
     *         @OA\JsonContent(
     *             required={"name", "customer_id"},
     *             @OA\Property(property="name", type="string", description="Nombre del chatbot"),
     *             @OA\Property(property="customer_id", type="integer", description="ID del cliente asociado al chatbot"),
     *             @OA\Property(property="creator_id", type="integer", description="ID del creador del chatbot (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Creación de chatbot exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true, description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", example="Creación de Chatbot exitosa", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false, description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", example="chatbot name already exist", description="Mensaje de error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false, description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.", description="Mensaje de error")
     *         )
     *     ),
     * )
     */

    public function store(Request $request)
    {
        $maxRetries = 3;
        $attempts = 0;

        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);

        while ($attempts < $maxRetries) {
            try {

                DB::beginTransaction();

                $request->validate([
                    'name' => [
                        'required',
                        'unique_chatbot_name_in_city_council:' . $request->input('customer_id'),
                    ],
                    'customer_id' => 'required',
                ]);
                $customerId = $request->input('customer_id');
                $creatorId = $request->input('creator_id', auth()->id());


                $customerExist = CityCouncils::where('id', $customerId)->exists();
                if (!$customerExist) {
                    return response()->json(['success' => false, 'message' => 'El id del cliente no es correcto.'], 404);
                }

                $creatorExist = User::where('id', $creatorId)->exists();
                if (!$creatorExist) {
                    return response()->json(['success' => false, 'message' => 'El id de usuario creador no es correcto'], 404);
                }

                $chatbot = Chatbot::create([
                    'name' => $request->input('name'),
                    'city_councils_id' => $request->input('customer_id'),
                    'creator_id' => $creatorId
                ]);

                ChatbotModification::create([
                    'chatbot_id' => $chatbot->id,
                    'action' => ChatbotModification::CHAT_CREATED,
                    'user_id' => auth()->id(),
                ]);

                $defaultSettings = DefaultSetting::all();

                foreach ($defaultSettings as $defaultSetting) {
                    $chatbotSetting = new ChatbotSetting();
                    $chatbotSetting->chatbot_id = $chatbot->id;
                    $chatbotSetting->default_id = $defaultSetting->id;
                    $chatbotSetting->value = $defaultSetting->value;
                    $chatbotSetting->save();
                }
                $subjects = [
                    ['name' => 'Mensajes chatbots', 'chatbot_id' => $chatbot->id, 'creator_id' => $creatorId],
                ];
                foreach ($subjects as $subject) {
                    $existingSubject = DB::table('subjects')
                        ->where('name', $subject['name'])
                        ->where('creator_id', $subject['creator_id'])
                        ->where('chatbot_id', $subject['chatbot_id'])
                        ->first();
                    if (!$existingSubject) {
                        Subject::create($subject);
                    }
                }
                $sub_msj_bot = Subject::where([['name', 'Mensajes chatbots'], ['chatbot_id', $chatbot->id]])->first();
                $intention_can = [
                    ['name' => 'cancelar', 'eng_name' => 'cancel', 'val_name' => 'cancellar', 'validated' => 1, 'creation_method' => 'WEB', 'creator' => auth()->id(), 'chatbot_id' => $chatbot->id, 'subjects_id' => $sub_msj_bot->id, 'training' => 0]
                ];

                //crear las 3 preguntas
                $first_can_questions = [
                    ['type' => null, 'ref' => 1],
                    ['type' => null, 'ref' => 2],
                    ['type' => null, 'ref' => 3]
                ];

                $first_can_answers = [
                    ['type' => null, 'ref' => 1],
                    ['type' => null, 'ref' => 2],
                    ['type' => null, 'ref' => 3]
                ];
                //a cada pregunta su lennguaje

                $question_can_languange = [
                    ['question' => 'Detengamos la conversación por ahora', 'language' => 'castellano', 'ref-lang' => 1],
                    ['question' => 'Cancelar esta conversación', 'language' => 'castellano', 'ref-lang' => 2],
                    ['question' => '¿Podemos parar aquí y retomar después?', 'language' => 'castellano', 'ref-lang' => 3],
                    ['question' => "Let's stop the conversation for now", 'language' => 'ingles', 'ref-lang' => 1],
                    ['question' => "Cancel this conversation", 'language' => 'ingles', 'ref-lang' => 2],
                    ['question' => "Can we stop here and pick up later?", 'language' => 'ingles', 'ref-lang' => 3],
                    ['question' => "Detinguem la conversa ara com ara", 'language' => 'valenciano', 'ref-lang' => 1],
                    ['question' => "Cancel·lar esta conversa", 'language' => 'valenciano', 'ref-lang' => 2],
                    ['question' => "Podemos parar ací i reprendre després?", 'language' => 'valenciano', 'ref-lang' => 3],
                ];
                $answers_can_language = [
                    ['answers' => '¡Entendido! Si necesitas retomar en otro momento, aquí estaré.', 'language' => 'castellano', 'ref-lang' => 1],
                    ['answers' => 'Perfecto, podemos cerrar la conversación por ahora. Vuelve cuando quieras continuar.', 'language' => 'castellano', 'ref-lang' => 2],
                    ['answers' => 'Entiendo, hemos terminado. Siempre puedes encontrarme aquí si necesitas más asistencia.', 'language' => 'castellano', 'ref-lang' => 3],
                    ['answers' => "Got it! If you need to pick up another time, I'll be here.", 'language' => 'ingles', 'ref-lang' => 1],
                    ['answers' => "Perfect, we can close the conversation for now. Come back when you want to continue.", 'language' => 'ingles', 'ref-lang' => 2],
                    ['answers' => "I understand, we are done. You can always find me here if you need further assistance.", 'language' => 'ingles', 'ref-lang' => 3],
                    ['answers' => "Entés! Si necessites reprendre en un altre moment, ací estaré.", 'language' => 'valenciano', 'ref-lang' => 1],
                    ['answers' => "Perfecte, podem tancar la conversa ara com ara. Torna quan vulgues continuar.", 'language' => 'valenciano', 'ref-lang' => 2],
                    ['answers' => "Entenc, hem acabat. Sempre pots trobar-me ací si necessites més assistència.", 'language' => 'valenciano', 'ref-lang' => 3],
                ];
                foreach ($intention_can as $intent) {
                    $existingIntention = DB::table('intentions')
                        ->where('name', $intent['name'])
                        ->where('creator', $intent['creator'])
                        ->where('chatbot_id', $intent['chatbot_id'])
                        ->where('subjects_id', $sub_msj_bot->id)
                        ->first();
                    if (!$existingIntention) {
                        $intentions = Intentions::create($intent);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intentions->name,
                            'language' => 'castellano'
                        ]);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intent['eng_name'],
                            'language' => 'ingles'
                        ]);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intent['val_name'],
                            'language' => 'valenciano'
                        ]);
                    }
                    foreach ($first_can_questions as $question) {
                        $newQuestion = Question::updateOrCreate(['id' => $question['id'] ?? null], ['intentions_id' => $intentions->id ?? null]);
                        foreach ($question_can_languange as $questionLang) {
                            if ($question['ref'] == $questionLang['ref-lang']) {
                                QuestionLanguage::updateOrCreate(['question_id' => $newQuestion->id ?? null, 'language' => $questionLang['language']], ['question' => $questionLang['question']]);
                            }
                        }
                    }
                    foreach ($first_can_answers as $answer) {
                        $newAnswer = Answers::updateOrCreate(['id' => $answer['id'] ?? null], ['intentions_id' => $intentions->id ?? null]);
                        foreach ($answers_can_language as $answerLang) {
                            if ($answer['ref'] == $answerLang['ref-lang']) {
                                AnswersLanguage::updateOrCreate(['answers_id' => $newAnswer->id ?? null, 'language' => $answerLang['language']], ['answers' => $answerLang['answers']]);
                            }
                        }
                    }
                }
                $intention_desv = [
                    ['name' => 'desvio_agente', 'eng_name' => 'agent_transfer', 'val_name' => 'transferir_agent', 'validated' => 1, 'creation_method' => 'WEB', 'creator' => auth()->id(), 'chatbot_id' => $chatbot->id, 'subjects_id' => $sub_msj_bot->id, 'training' => 0]
                ];

                $first_desv_questions = [
                    ['type' => null, 'ref' => 1],
                    ['type' => null, 'ref' => 2],
                    ['type' => null, 'ref' => 3]
                ];

                $first_desv_answers = [
                    ['type' => null, 'ref' => 1],
                    ['type' => null, 'ref' => 2],
                    ['type' => null, 'ref' => 3]
                ];

                $question_desv_language = [
                    ['question' => '¿Puedo hablar con un agente humano?', 'language' => 'castellano', 'ref-lang' => 1],
                    ['question' => 'Por favor, transfiereme a un agente.', 'language' => 'castellano', 'ref-lang' => 2],
                    ['question' => 'No estoy obteniendo la ayuda que necesito, ¿puedo hablar con un agente?', 'language' => 'castellano', 'ref-lang' => 3],
                    ['question' => 'Can I speak to a human agent?', 'language' => 'ingles', 'ref-lang' => 1],
                    ['question' => 'Please transfer me to an agent.', 'language' => 'ingles', 'ref-lang' => 2],
                    ['question' => 'I am not getting the help I need, can I speak to an agent?', 'language' => 'ingles', 'ref-lang' => 3],
                    ['question' => 'Puc parlar amb un agent humà?', 'language' => 'valenciano', 'ref-lang' => 1],
                    ['question' => 'Per favor, transfiereme a un agent.', 'language' => 'valenciano', 'ref-lang' => 2],
                    ['question' => "No estic obtenint l'ajuda que necessite, puc parlar amb un agent?", 'language' => 'valenciano', 'ref-lang' => 3],
                ];
                $answers_desv_language = [
                    ['answers' => 'Entiendo, te conectaré con un agente humano lo antes posible.', 'language' => 'castellano', 'ref-lang' => 1],
                    ['answers' => 'Lo siento por la dificultad. Voy a conectarte un agente en vivo.', 'language' => 'castellano', 'ref-lang' => 2],
                    ['answers' => 'Entiendo que necesitas asistencia más específica. Te conectaré con un agente humano ahora mismo.', 'language' => 'castellano', 'ref-lang' => 3],
                    ['answers' => 'I understand you need more specific assistance. I will connect you with a human agent right away.', 'language' => 'ingles', 'ref-lang' => 1],
                    ['answers' => "Sorry for the difficulty. I'm going to connect a live agent to you.", 'language' => 'ingles', 'ref-lang' => 2],
                    ['answers' => "I understand you need more specific assistance. I will connect you with a human agent right away.", 'language' => 'ingles', 'ref-lang' => 3],
                    ['answers' => "Entenc, et connectaré amb un agent humà al més prompte possible.", 'language' => 'valenciano', 'ref-lang' => 1],
                    ['answers' => "Ho sent per la dificultat. Et connectaré un agent en viu.", 'language' => 'valenciano', 'ref-lang' => 2],
                    ['answers' => "Entenc que necessites assistència més específica. Et connectaré amb un agent humà ara mateix.", 'language' => 'valenciano', 'ref-lang' => 3],
                ];
                foreach ($intention_desv as $intent) {
                    $existingIntention = DB::table('intentions')
                        ->where('name', $intent['name'])
                        ->where('creator', $intent['creator'])
                        ->where('chatbot_id', $intent['chatbot_id'])
                        ->where('subjects_id', $sub_msj_bot->id)
                        ->first();
                    if (!$existingIntention) {
                        $intentions = Intentions::create($intent);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intentions->name,
                            'language' => 'castellano'
                        ]);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intent['eng_name'],
                            'language' => 'ingles'
                        ]);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intent['val_name'],
                            'language' => 'valenciano'
                        ]);
                    }
                    foreach ($first_desv_questions as $question) {
                        $newQuestion = Question::updateOrCreate(['id' => $question['id'] ?? null], ['intentions_id' => $intentions->id ?? null]);
                        foreach ($question_desv_language as $questionLang) {
                            if ($question['ref'] == $questionLang['ref-lang']) {
                                QuestionLanguage::updateOrCreate(['question_id' => $newQuestion->id ?? null, 'language' => $questionLang['language']], ['question' => $questionLang['question']]);
                            }
                        }
                    }
                    foreach ($first_desv_answers as $answer) {
                        $newAnswer = Answers::updateOrCreate(['id' => $answer['id'] ?? null], ['intentions_id' => $intentions->id ?? null]);
                        foreach ($answers_desv_language as $answerLang) {
                            if ($answer['ref'] == $answerLang['ref-lang']) {
                                AnswersLanguage::updateOrCreate(['answers_id' => $newAnswer->id ?? null, 'language' => $answerLang['language']], ['answers' => $answerLang['answers']]);
                            }
                        }
                    }
                }
                $intention_not = [
                    ['name' => 'no_le_he_entendido', 'eng_name' => 'i_did_not_understand', 'val_name' => 'no_he_entes', 'validated' => 1, 'creation_method' => 'WEB', 'creator' => auth()->id(), 'chatbot_id' => $chatbot->id, 'subjects_id' => $sub_msj_bot->id, 'training' => 0]
                ];

                $first_not_answers = [
                    ['type' => null, 'ref' => 1],
                    ['type' => null, 'ref' => 2],
                    ['type' => null, 'ref' => 3]
                ];

                $answers_not_language = [
                    ['answers' => 'Lo siento, no estoy seguro de entender. ¿Podrías reformular tu pregunta de otra manera?', 'language' => 'castellano', 'ref-lang' => 1],
                    ['answers' => 'Perdona, estoy un poco confundido. ¿Podrías ser más específico?', 'language' => 'castellano', 'ref-lang' => 2],
                    ['answers' => 'No logro captar tu mensaje. ¿Podrías proporcionar más detalles?', 'language' => 'castellano', 'ref-lang' => 3],
                    ['answers' => "I'm sorry, I'm not sure I understand, could you rephrase your question?", 'language' => 'ingles', 'ref-lang' => 1],
                    ['answers' => "Sorry, I'm a little confused, could you be more specific?", 'language' => 'ingles', 'ref-lang' => 2],
                    ['answers' => "I can't get your message, could you provide more details?", 'language' => 'ingles', 'ref-lang' => 3],
                    ['answers' => "Ho sent, no estic segur d'entendre. Podries reformular la teua pregunta d'una altra manera?", 'language' => 'valenciano', 'ref-lang' => 1],
                    ['answers' => "Perdona, estic un poc confós. Podries ser més específic?", 'language' => 'valenciano', 'ref-lang' => 2],
                    ['answers' => "No aconseguisc captar el teu missatge. Podries proporcionar més detalls?", 'language' => 'valenciano', 'ref-lang' => 3],
                ];
                foreach ($intention_not as $intent) {
                    $existingIntention = DB::table('intentions')
                        ->where('name', $intent['name'])
                        ->where('creator', $intent['creator'])
                        ->where('chatbot_id', $intent['chatbot_id'])
                        ->where('subjects_id', $sub_msj_bot->id)
                        ->first();
                    if (!$existingIntention) {
                        $intentions = Intentions::create($intent);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intentions->name,
                            'language' => 'castellano'
                        ]);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intent['eng_name'],
                            'language' => 'ingles'
                        ]);
                        IntentionLanguage::create([
                            'intention_id' => $intentions->id,
                            'name' => $intent['val_name'],
                            'language' => 'valenciano'
                        ]);
                    }
                    foreach ($first_not_answers as $answer) {
                        $newAnswer = Answers::updateOrCreate(['id' => $question['id'] ?? null], ['intentions_id' => $intentions->id ?? null]);
                        foreach ($answers_not_language as $answerLang) {
                            if ($answer['ref'] == $answerLang['ref-lang']) {
                                AnswersLanguage::updateOrCreate(['answers_id' => $newAnswer->id ?? null, 'language' => $answerLang['language']], ['answers' => $answerLang['answers']]);
                            }
                        }
                    }
                }
                $response = $this->createDefaultSchedule($chatbot->id);
                DB::commit();

                $this->createChatbotPort($chatbot->id);

                return response()->json(['success' => true, 'message' => 'Creación de Chatbot exitosa', 'data' => $chatbot], 202);
            } catch (\Illuminate\Database\QueryException $ex) {
                DB::rollBack();

                // Si el error es un timeout, incrementa el número de intentos
                if ($ex->getCode() == 1205) {
                    $attempts++;
                    if ($attempts == $maxRetries) {
                        Log::error($ex->getMessage(), ['exception' => $ex]);
                        return response()->json(['success' => false, 'message' => 'Transaction failed after multiple attempts.'], 500);
                    }
                } else {
                    // Si no es un timeout, lanza la excepción
                    throw $ex;
                }
            } catch (\Throwable $th) {
                DB::rollBack();
                if (property_exists($th, 'validator') && method_exists($th->validator, 'errors')) {
                    $errors = $th->validator->errors();
                    if ($errors->has('name') && $errors->first('name') === 'validation.unique_chatbot_name_in_city_council') {
                        return response()->json(['success' => false, 'message' => 'chatbot name already exist'], 400);
                    } else {
                        return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $errors], 400);
                    }
                } else {
                    Log::error($th->getMessage(), ['exception' => $th]);
                    return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
                }
            }
        }
    }

    private function createChatbotPort($chatbot)
    {
        $lastPort = ChatbotPort::max('port');
        $newPort = ($lastPort !== null) ? $lastPort + 1 : 5005;

        while (ChatbotPort::where('port', $newPort)->exists()) {
            $newPort++;
        }

        $chatbotPort = new ChatbotPort;
        $chatbotPort->chatbots_id = $chatbot;
        $chatbotPort->port = $newPort;
        $chatbotPort->language = "castellano";
        $chatbotPort->save();

        Log::info('Datos enviados createbot', ['chatbotid' => $chatbot, 'port' => $newPort]);
        $rasaControl = new RasaBotControl();
        $result = $rasaControl->createBot($chatbot, "castellano", $newPort);
        Log::info('Datos recibidos createbot', ['result' => $result]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 404);

        try {
            $request->validate([
                'information' => 'sometimes|required',
            ]);

            $chatbot = Chatbot::findOrFail($id);

            if ($request->has('information')) {
                $chatbot->information = $request->input('information');

                ChatbotModification::create([
                    'chatbot_id' => $chatbot->id,
                    'action' => ChatbotModification::FLOW_UPDATED,
                    'user_id' => auth()->id(),
                ]);

                $newChatbotLog = ChatbotLog::create([
                    'chatbot_id' => $chatbot->id,
                    'user_id' => auth()->id(),
                    'flow' => $request->input('information')
                ]);
                if (isset($newChatbotLog->id)) {
                    $this->establishTransitions($newChatbotLog->flow, $newChatbotLog->id);
                }
            };

            $chatbot->save();


            return response()->json(['success' => true, 'message' => 'Actualización de Chatbot exitosa.'], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Elimina un chatbot por su ID.
     *
     * @param string $id ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteChatbotApi/{id}",
     *     tags={"chatbots"},
     *     summary="Elimina un chatbot",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del chatbot a eliminar en la URL",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chatbot eliminado correctamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo eliminar el chatbot"
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);

        try {
            $chatbot = Chatbot::find($id);
            if (!$chatbot) {
                return response()->json(['success' => false, 'message' => 'El id de chatbot ingresado no es valido.'], 404);
            }

            $ports = $chatbot->ports;
            $languages = [];
            foreach ($ports as $port) {
                $languages[] = $port->language;
            }

            $uniqueLanguages = array_unique($languages);
            $rasaControl = new RasaBotControl();

            // Intentar eliminar bots para cada lenguaje
            foreach ($uniqueLanguages as $language) {
                try {
                    Log::info('Datos enviados deleteBot', [
                        'chatbotid' => $id,
                        'language' => $language
                    ]);
                    $result = $rasaControl->deleteBot($id, $language);  // Eliminar bot
                    Log::info('Datos recibidos deleteBot', [
                        'result' => $result,
                        'language' => $language
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Error al eliminar bot para el lenguaje', [
                        'chatbotid' => $id,
                        'language' => $language,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $chatbot->update(['active' => 0]);
            $chatbot->delete();

            ChatbotModification::create([
                'chatbot_id' => $chatbot->id,
                'action' => ChatbotModification::CHAT_DELETED,
                'user_id' => auth()->id(),
            ]);

            return response()->json(['success' => true, 'message' => 'Chatbot eliminado correctamente.'], 200);
        } catch (\Throwable $th) {
            Log::error('Error inesperado al eliminar chatbot', [
                'chatbotid' => $id,
                'error' => $th->getMessage()
            ]);

            return $errorMessage;
        }
    }


    public function updateStateChatbot(Request $request)
    {
        try {

            $request->validate([
                'id' => 'required',
                'active' => 'required',
            ]);

            $chatbot = Chatbot::findOrFail($request->id);

            if (empty($chatbot->information)) {
                return response()->json(['success' => false, 'message' => 'not flow.'], 400);
            }

            $chatbot->active = !$request->active;
            $chatbot->save();

            ChatbotModification::create([
                'chatbot_id' => $chatbot->id,
                'action' => $request->active ? ChatbotModification::CHAT_DISABLED : ChatbotModification::CHAT_ENABLED,
                'user_id' => auth()->id(),
            ]);
            $rasaControl = new RasaBotControl();
            if (!$request->active) {
                Log::info('Datos enviados startBot', ['chatbotid' => $request->id]);
                $result = $rasaControl->startBot($request->id);
                Log::info('Datos recibidos startBot', ['result' => $result]);
            } else {
                Log::info('Datos enviados stopBot', ['chatbotid' => $request->id]);
                $result = $rasaControl->stopBot($request->id);
                Log::info('Datos recibidos stopBot', ['result' => $result]);
            }

            return response()->json(['success' => true, 'message' => 'Actualización de Chatbot exitosa', 'data' => ['status' => $chatbot->active]], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['message' => $th->getMessage(), 'line' => $th->getLine()]);
            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 404);
        }
    }

    /**
     * Obtiene los registros de logs de un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/getLogBuilderApi",
     *     tags={"chatbots"},
     *     summary="Obtiene los registros de logs de un chatbot",
     *     @OA\RequestBody(
     *         required=true,
     *         description="ID del chatbot",
     *         @OA\JsonContent(
     *             required={"chatbot_id"},
     *             @OA\Property(property="chatbot_id", type="string", description="ID del chatbot")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Se devuelven los registros de logs del chatbot"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado"
     *     )
     * )
     */
    public function getLogBuilder(Request $request)
    {
        try {

            $request->validate([
                'chatbot_id' => 'required',
            ]);

            $chatbotId = $request->input('chatbot_id');

            $chatbotLogs = ChatbotLog::select('id', 'flow', 'created_at', 'user_id')
                ->where('chatbot_id', $chatbotId)
                ->where('created_at', '<', function ($query) use ($chatbotId) {
                    $query->select('created_at')
                        ->from('chatbot_logs')
                        ->where('chatbot_id', $chatbotId)
                        ->orderBy('created_at', 'desc')
                        ->limit(1);
                })
                ->with('user')
                ->get();

            return response()->json(['success' => true, 'data' => $chatbotLogs], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['message' => $th->getMessage(), 'line' => $th->getLine()]);
            return response()->json(['success' => false, 'message' => 'an unexpected error occurred.'], 404);
        }
    }

    public function createDefaultSchedule($chatbotId)
    {
        try {

            $normalSchedule = new Schedule();
            $normalSchedule->active = '0';
            $normalSchedule->type = 'normal';
            $normalSchedule->save();

            $specialSchedule = new Schedule();
            $specialSchedule->active = '0';
            $specialSchedule->type = 'special';
            $specialSchedule->save();

            $normalResponse = $this->setSchedule($chatbotId, $normalSchedule->id);
            $specialResponse = $this->setSchedule($chatbotId, $specialSchedule->id);

            if ($normalResponse && $specialResponse) {
                return true;
            } else {
                return false;
            }
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return false;
        }
    }

    public function setSchedule($chatbotId, $scheduleId)
    {
        try {
            $timeSlots = TimeSlot::all();
            foreach ($timeSlots as $slot) {
                $days = Day::all();
                foreach ($days as $day) {
                    $newDaySlot = new DayTimeSlot();
                    $newDaySlot->id_day = $day->id;
                    $newDaySlot->id_time_slot = $slot->id;

                    switch ($slot->name) {
                        case 'franja1':
                            $newDaySlot->start_time = '07:00';
                            $newDaySlot->end_time = '11:00';
                            break;
                        case 'franja2':
                            $newDaySlot->start_time = '12:00';
                            $newDaySlot->end_time = '16:00';
                            break;

                        default:
                    }

                    $newDaySlot->save();

                    $newScheduleSlot = new ScheduleDayTimeSLot();
                    $newScheduleSlot->id_chatbot = $chatbotId;
                    $newScheduleSlot->id_schedule = $scheduleId;
                    $newScheduleSlot->id_day_time_slot = $newDaySlot->id;

                    $newScheduleSlot->save();
                }
            }

            return true;
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th]);
            return false;
        }
    }
    /**
     * Obtiene la información de un chatbot por su ID.
     *
     * @param string $id El ID del chatbot.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getIdChatbotApi/{id}",
     *     tags={"chatbots"},
     *     summary="Obtiene un chatbot por su ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del chatbot obtenida exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Chatbot no encontrado"
     *     )
     * )
     */
    public function getIdChatbot($id)
    {
        $dataChatbot = Chatbot::find($id);
        return response()->json(['success' => true, 'data' => $dataChatbot], 200);
    }

    /**
     * Actualiza el nombre de un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/setEditChatbotApi",
     *     tags={"chatbots"},
     *     summary="Actualiza el nombre de un chatbot",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de actualización del nombre del chatbot",
     *         @OA\JsonContent(
     *             required={"name", "id"},
     *             @OA\Property(property="name", type="string", description="Nuevo nombre del chatbot"),
     *             @OA\Property(property="id", type="string", description="ID del chatbot a actualizar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Actualización de chatbot exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true, description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", example="Actualización de Chatbot exitosa", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="El nombre del chatbot ya está en uso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false, description="Indica si la operación fue fallida"),
     *             @OA\Property(property="message", type="string", example="El nombre del chatbot ya está en uso", description="Mensaje de error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Hubo un error al actualizar el chatbot",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false, description="Indica si la operación fue fallida"),
     *             @OA\Property(property="message", type="string", example="Hubo un error al actualizar el chatbot", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function setEditChatbot(Request $request)
    {
        try {
            $request->validate([
                'name' => ['required'],
            ]);

            $validId = Chatbot::where('id', $request->id)->exists();
            if (!$validId) {
                return response()->json(['success' => false, 'message' => 'El id de chatbot no es valido'], 404);
            }

            $existingChatbot = Chatbot::where('name', $request->input('name'))->where('id', '!=', $request->id)->exists();

            if ($existingChatbot) {
                return response()->json(['success' => false, 'message' => 'El nombre del chatbot ya está en uso'], 400);
            }

            $chatbot = Chatbot::findOrFail($request->id);

            $chatbot->update([
                'name' => $request->input('name'),
            ]);

            return response()->json(['success' => true, 'message' => 'Actualización de Chatbot exitosa'], 202);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Hubo un error al actualizar el chatbot'], 500);
        }
    }

    public function establishTransitions($flow, $chatbotLogId)
    {
        $diagramId = "";
        $arrayButtons = array();
        $arrayNodes = array();

        $data = $flow;

        $arrayData = json_decode($data, true);
        $firstNode = reset($arrayData['drawflow']['Home']['data']);

        if (isset($chatbotLogId)) {
            $diagramId = $chatbotLogId;
        } else {
            Log::error('No existe id_chatbot');
            throw new \ErrorException('No existe id_chatbot');
        }

        DB::beginTransaction();

        try {
            foreach ($arrayData['drawflow']['Home']['data'] as $node) {
                $typeNode = null;

                if (isset($node['data']['info'])) {

                    if (isset($node['data']['type'])) {
                        $typeNode = $node['data']['type'];
                    }

                    $arrayButtons = array();
                    if (isset($node['data']['info']['buttons'])) {
                        foreach ($node['data']['info']['buttons'] as $key => $value) {
                            $arrayButtons[] = $value['button'];
                        }
                    }
                }
                $newNode = Node::create([
                    'node' => $node['id'],
                    'name' => $node['name'],
                    'class' => $node['class'],
                    'html' => $node['html'],
                    'typenode' => $typeNode,
                    'chatbot_log_id' => $diagramId,
                ]);

                if (isset($node['data']['info']['messages'])) {
                    foreach ($node['data']['info']['messages'] as $message) {
                        if (isset($message['message']) && $message['message'] != '') {
                            NodeLanguage::create([
                                'text' => $message['message'],
                                'language' => $message['language'],
                                'node_id' => $newNode->id,
                            ]);
                        }
                    }
                }

                if (isset($node['data']['info']['intentions'])) {
                    foreach ($node['data']['info']['intentions'] as $intention) {
                        if (isset($intention['id']) && $intention['name'] != '') {
                            NodeIntention::create([
                                'intention_id' => $intention['id'],
                                'node_id' => $newNode->id,
                            ]);
                        }
                    }
                }

                $arrayNodes = array();

                foreach ($node['outputs'] as $key => $connections) {
                    foreach ($connections as $output) {

                        foreach ($output as $id => $nodeOutput) {
                            if (isset($nodeOutput['output'])) {
                            }
                            $arrayNodes[] = $nodeOutput['node'];
                        }
                    }
                }

                if (count($arrayButtons) == 0) {
                    $arrayButtons[] = '*';
                }

                if (count($arrayButtons) != count($arrayNodes)) {
                } else {

                    foreach ($arrayButtons as $i => $button) {

                        $transicion = NodeTransition::create([
                            'origin' => $node['id'],
                            'transition' => $button,
                            'destination' => $arrayNodes[$i],
                            'chatbot_log_id' => $diagramId,
                        ]);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            Log::error('Error en la transacción: ' . $e->getMessage() . ' line ' . $e->getLine());
            throw new \ErrorException('Error al insertar transiciones');
        }
    }

    /**
     * Obtiene el historial de modificaciones de un chatbot por su ID.
     *
     * @param string $chatbotId ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getHistoryChatbotsApi/{chatbotId}",
     *     tags={"chatbots"},
     *     summary="Obtiene el historial de modificaciones de un chatbot",
     *     @OA\Parameter(
     *         name="chatbotId",
     *         in="path",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historial de modificaciones obtenido correctamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo obtener el historial del chatbot"
     *     )
     * )
     */
    public function getHistoryChatbots($chatbotId)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un problema al listar el historial del chatbot.'], 404);
        try {

            $modifications = Chatbot::where('id', $chatbotId)->with(['modifications' => function ($query) {
                $query->select('id', 'action', 'chatbot_id', 'user_id', 'created_at');
                $query->with(['user' => function ($userQuery) {
                    $userQuery->select('id', 'name');
                }]);
            }])->first(['id']);

            return response()->json(['success' => true, 'data' => $modifications], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    public function trainingChatbot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chatbot_id' => ['required']
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $languages = ['castellano', 'ingles', 'valenciano'];
        $idiomasValidos = [];

        foreach ($languages as $language) {
            $idiomasValidos[$language] = ChatbotSetting::with('defaultTable')
                ->withTrashed()
                ->where('chatbot_id', $request->chatbot_id)
                ->whereHas('defaultTable', function ($query) use ($language) {
                    $query->whereIn('name', [$language]);
                })
                ->value('value');
        }

        foreach ($idiomasValidos as $idioma => $valido) {
            if ($valido === "1") {
                TrainChatbot::dispatch($request->input('chatbot_id'), $idioma);
            }
        }
        return response()->json(['status' => 'Training initiated'], 200);
    }

    /**
     * Obtiene los chatbots activos según los criterios especificados. Puedes obtener información de un chatbot específico, de los chatbots de un cliente en particular o de todos los chatbots en el sistema.
     *
     * @param \Illuminate\Http\Request $request La solicitud HTTP con los parámetros de filtrado.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getActiveChatbots",
     *     tags={"chatbots"},
     *     summary="Obtiene los chatbots activos",
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Fecha de inicio del rango (yyyy-mm-dd)(opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha de fin del rango (yyyy-mm-dd)(opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="idCustomer",
     *         in="query",
     *         description="ID del cliente (opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="idChatbot",
     *         in="query",
     *         description="ID del chatbot (opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="uuid"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Chatbots activos obtenidos correctamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Chatbots activos obtenidos correctamente"
     *             ),
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="chatbots",
     *                 type="object",
     *                 @OA\Property(
     *                     property="active_chatbots",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="string",
     *                             format="uuid",
     *                             example="7abe36d6-4584-4ffe-8b62-556706380a3f"
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Chatbot 1"
     *                         ),
     *                         @OA\Property(
     *                             property="active",
     *                             type="number",
     *                             example=1
     *                         ),
     *                         @OA\Property(
     *                             property="current_conversations",
     *                             type="number",
     *                             example=1
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="inactive_chatbots",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(
     *                             property="id",
     *                             type="string",
     *                             format="uuid",
     *                             example="7abe36d6-4584-4ffe-8b62-556706380a3f"
     *                         ),
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             example="Chatbot 2"
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Error en la solicitud: ..."
     *             ),
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Error interno del servidor."
     *             ),
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             )
     *         )
     *     )
     * )
     */
    public function getActiveChatbots(Request $request)
    {

        try {
            $from = $request->input('from');
            $to = $request->input('to');
            $idCustomer = $request->input('idCustomer');
            $idChatbot = $request->input('idChatbot');

            if ((!$from && $to) || ($from && !$to)) {
                return response()->json(['success' => false, 'message' => 'Por favor si vas a ingresar un rango de fecha, debes ingresar tanto "from" como "to"'], 404);
            }

            if ($from && $to) {
                if (!$this->isValidDate($from) || !$this->isValidDate($to)) {
                    return response()->json(['success' => false, 'message' => 'Los campos from y to deben ser fechas válidas en formato yyyy-mm-dd.'], 404);
                }
            }

            if (!$idCustomer && !$idChatbot) {
                $response = $this->allActiveChatbot($from, $to);
            } elseif ($idChatbot) {
                $chatbotExist = Chatbot::where('id', $idChatbot)->exists();
                if ($chatbotExist) {
                    $response = $this->oneActiveChatbot($idChatbot, $from, $to);
                } else {
                    return response()->json(['success' => false, 'message' => 'El id de chatbot proporcionado es invalido.'], 404);
                }
            } elseif ($idCustomer && !$idChatbot) {
                $customerExist = CityCouncils::where('id', $idCustomer)->exists();
                if ($customerExist) {
                    $response = $this->customerActiveChatbot($idCustomer, $from, $to);
                } else {
                    return response()->json(['success' => false, 'message' => 'El id de cliente proporcionado es invalido.'], 404);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Por favor revisa todos tus datos. No hemos podido procesar la solicitud.'], 404);
            }

            return $response;
        } catch (\Throwable $th) {
            Log::error('Error en getActiveChatbots: ' . $th->getMessage());
            return response()->json(['message' => 'Error interno del servidor', 'success' => false], 500);
        }
    }

    private function oneActiveChatbot($idChatbot, $from, $to)
    {
        if (!$from || !$to) {
            $activeChatbot = Chatbot::where('id', $idChatbot)->with('cityCouncil')
                ->select('id', 'name', 'city_councils_id', 'active', 'created_at')
                ->first();

            $conversationStatusId = ConversationStatus::where('name', 'En Curso')->value('id');

            $chatbotLogIds = ChatbotLog::where('chatbot_id', $idChatbot)->pluck('id');
            $metricsQuery = Conversation::where('conversation_status_id', $conversationStatusId)
                ->whereIn('chatbot_log_id', $chatbotLogIds)
                ->count();

            $activeChatbot->current_conversations = $metricsQuery;

            if ($activeChatbot->active == 0) {
                return response()->json([
                    'message' => 'El chatbot solicitado no se encuentra ACTIVO. Este chatbot pertenece al cliente ' . $activeChatbot->cityCouncil->name,
                    'success' => false,
                    'customer' => [
                        'id' => $activeChatbot->cityCouncil->id,
                        'name' => $activeChatbot->cityCouncil->name,
                        'chatbot' => $activeChatbot->makeHidden('cityCouncil')
                    ]
                ]);
            } else {
                return response()->json([
                    'message' => 'El chatbot solicitado se encuentra ACTIVO. Este chatbot pertenece al cliente ' . $activeChatbot->cityCouncil->name,
                    'success' => true,
                    'customer' => [
                        'id' => $activeChatbot->cityCouncil->id,
                        'name' => $activeChatbot->cityCouncil->name,
                        'chatbot' => $activeChatbot->makeHidden('cityCouncil')
                    ]
                ]);
            }
        } else {
            $fromDateTime = Carbon::parse($from)->startOfDay();
            $toDateTime = Carbon::parse($to)->endOfDay();

            $activeChatbot = Chatbot::where('id', $idChatbot)->with('cityCouncil')
                ->select('id', 'name', 'city_councils_id', 'active', 'created_at')
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->first();
            if (!$activeChatbot) {
                return response()->json([
                    'message' => 'No se encontro chatbot dentro de ese rango de fechas',
                    'success' => false
                ]);
            } else {
                if ($activeChatbot->active == 0) {
                    return response()->json([
                        'message' => 'El chatbot solicitado esta en fecha pero no se encuentra activo. Este chatbot pertenece al cliente ' . $activeChatbot->cityCouncil->name,
                        'success' => false,
                        'customer' => [
                            'id' => $activeChatbot->cityCouncil->id,
                            'name' => $activeChatbot->cityCouncil->name,
                            'chatbot' => $activeChatbot->makeHidden('cityCouncil')
                        ],
                    ]);
                } else {
                    return response()->json([
                        'message' => 'El chatbot solicitado está en fecha y se encuentra activo. Este chatbot pertenece al cliente ' . $activeChatbot->cityCouncil->name,
                        'success' => true,
                        'customer' => [
                            'id' => $activeChatbot->cityCouncil->id,
                            'name' => $activeChatbot->cityCouncil->name,
                            'chatbot' => $activeChatbot->makeHidden('cityCouncil')
                        ],
                    ]);
                }
            }
        }
    }

    private function allActiveChatbot($from, $to)
    {
        if (!$from || !$to) {
            $allChatbots = Chatbot::with(['cityCouncil' => function ($query) {
                $query->select('id', 'name');
            }])
                ->select('id', 'name', 'city_councils_id', 'active', 'created_at')
                ->get();

            if (!$allChatbots) {
                return response()->json([
                    'message' => 'No se encontro chatbot',
                    'success' => false
                ]);
            }

            $activeChatbots = $allChatbots->filter(function ($chatbot) {
                return $chatbot->active == 1;
            });
            $inactiveChatbots = $allChatbots->filter(function ($chatbot) {
                return $chatbot->active == 0;
            });

            $activeChatbots = $activeChatbots->sortBy(function ($chatbot) {
                return $chatbot->cityCouncil->name;
            })->values();

            $inactiveChatbots = $inactiveChatbots->sortBy(function ($chatbot) {
                return $chatbot->cityCouncil->name;
            })->values();

            $messageReturn = '';
            $successReturn = false;

            if ($activeChatbots->count() > 0) {

                $conversationStatusId = ConversationStatus::where('name', 'En Curso')->value('id');

                foreach ($activeChatbots as $chatbot) {
                    $chatbotLogIds = ChatbotLog::where('chatbot_id', $chatbot->id)->pluck('id');
                    $metricsQuery = Conversation::where('conversation_status_id', $conversationStatusId)
                        ->whereIn('chatbot_log_id', $chatbotLogIds)
                        ->count();

                    $chatbot->current_conversations = $metricsQuery;
                }

                $messageReturn = 'Se encontraron chatbots en estado ACTIVO.';
                $successReturn = true;
            } else {
                $messageReturn = 'No se encontraron chatbots en estado ACTIVO.';
                $successReturn = false;
            }

            return response()->json([
                'message' => $messageReturn,
                'success' => $successReturn,
                'chatbots' => [
                    'active_chatbots' => $activeChatbots,
                    'inactive_chatbots' => $inactiveChatbots
                ]
            ]);
        } else {
            $fromDateTime = Carbon::parse($from)->startOfDay();
            $toDateTime = Carbon::parse($to)->endOfDay();

            $allChatbots = Chatbot::with(['cityCouncil' => function ($query) {
                $query->select('id', 'name');
            }])
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->select('id', 'name', 'city_councils_id', 'active', 'created_at')
                ->get();

            if (!$allChatbots) {
                return response()->json([
                    'message' => 'No se encontro chatbot en ese rango de fechas',
                    'success' => false
                ]);
            }

            $activeChatbots = $allChatbots->filter(function ($chatbot) {
                return $chatbot->active == 1;
            });
            $inactiveChatbots = $allChatbots->filter(function ($chatbot) {
                return $chatbot->active == 0;
            });

            $activeChatbots = $activeChatbots->sortBy(function ($chatbot) {
                return $chatbot->cityCouncil->name;
            })->values();

            $inactiveChatbots = $inactiveChatbots->sortBy(function ($chatbot) {
                return $chatbot->cityCouncil->name;
            })->values();

            $messageReturn = '';
            $successReturn = false;

            if ($activeChatbots->count() > 0) {

                $conversationStatusId = ConversationStatus::where('name', 'En Curso')->value('id');

                foreach ($activeChatbots as $chatbot) {
                    $chatbotLogIds = ChatbotLog::where('chatbot_id', $chatbot->id)->pluck('id');
                    $metricsQuery = Conversation::where('conversation_status_id', $conversationStatusId)
                        ->whereIn('chatbot_log_id', $chatbotLogIds)
                        ->count();

                    $chatbot->current_conversations = $metricsQuery;
                }

                $messageReturn = 'Se encontraron chatbots en estado ACTIVO en ese rango de fechas.';
                $successReturn = true;
            } else {
                $messageReturn = 'No se encontraron chatbots en estado ACTIVO en ese rango de fechas.';
                $successReturn = false;
            }

            return response()->json([
                'message' => $messageReturn,
                'success' => $successReturn,
                'chatbots' => [
                    'active_chatbots' => $activeChatbots,
                    'inactive_chatbots' => $inactiveChatbots
                ]
            ]);
        }
    }

    private function customerActiveChatbot($idCustomer, $from, $to)
    {
        if (!$from || !$to) {

            $customerInfo = CityCouncils::where('id', $idCustomer)->first();
            $customerChatbots = Chatbot::where('city_councils_id', $idCustomer)
                ->select('id', 'name', 'city_councils_id', 'active', 'created_at')
                ->get();
            if (!$customerChatbots) {
                return response()->json([
                    'message' => 'No se encontro chatbot relacionado a ese cliente',
                    'success' => false
                ]);
            }

            $activeChatbots = $customerChatbots->filter(function ($chatbot) {
                return $chatbot->active == 1;
            });
            $inactiveChatbots = $customerChatbots->filter(function ($chatbot) {
                return $chatbot->active == 0;
            });

            $messageReturn = '';
            $successReturn = false;

            if ($activeChatbots->count() > 0) {

                $conversationStatusId = ConversationStatus::where('name', 'En Curso')->value('id');

                foreach ($activeChatbots as $chatbot) {
                    $chatbotLogIds = ChatbotLog::where('chatbot_id', $chatbot->id)->pluck('id');
                    $metricsQuery = Conversation::where('conversation_status_id', $conversationStatusId)
                        ->whereIn('chatbot_log_id', $chatbotLogIds)
                        ->count();

                    $chatbot->current_conversations = $metricsQuery;
                }

                $messageReturn = 'Se encontraron chatbots del cliente en estado ACTIVO.';
                $successReturn = true;
            } else {
                $messageReturn = 'No se encontraron chatbots del cliente en estado ACTIVO.';
                $successReturn = false;
            }

            return response()->json([
                'message' => $messageReturn,
                'success' => $successReturn,
                'customer' => [
                    'id' => $customerInfo->id,
                    'name' => $customerInfo->name,
                    'active_chatbots' => $activeChatbots,
                    'inactive_chatbots' => $inactiveChatbots
                ]
            ]);
        } else {
            $fromDateTime = Carbon::parse($from)->startOfDay();
            $toDateTime = Carbon::parse($to)->endOfDay();

            $customerInfo = CityCouncils::where('id', $idCustomer)->first();

            $customerChatbots = Chatbot::where('city_councils_id', $idCustomer)
                ->whereBetween('created_at', [$fromDateTime, $toDateTime])
                ->select('id', 'name', 'city_councils_id', 'active', 'created_at')
                ->get();

            if ($customerChatbots->count() == 0) {
                return response()->json([
                    'message' => 'No se encontraron chatbots del cliente en ese rango de fechas.',
                    'success' => false,
                    'customer' => [
                        'id' => $customerInfo->id,
                        'name' => $customerInfo->name,
                        'active_chatbots' => [],
                        'inactive_chatbots' => []
                    ]
                ]);
            } else {
                $activeChatbots = $customerChatbots->filter(function ($chatbot) {
                    return $chatbot->active == 1;
                });
                $inactiveChatbots = $customerChatbots->filter(function ($chatbot) {
                    return $chatbot->active == 0;
                });

                $messageReturn = '';
                $successReturn = false;

                if ($activeChatbots->count() > 0) {

                    $conversationStatusId = ConversationStatus::where('name', 'En Curso')->value('id');

                    foreach ($activeChatbots as $chatbot) {
                        $chatbotLogIds = ChatbotLog::where('chatbot_id', $chatbot->id)->pluck('id');
                        $metricsQuery = Conversation::where('conversation_status_id', $conversationStatusId)
                            ->whereIn('chatbot_log_id', $chatbotLogIds)
                            ->count();

                        $chatbot->current_conversations = $metricsQuery;
                    }

                    $messageReturn = 'Se encontraron chatbots del cliente en estado ACTIVO en ese rango de fechas.';
                    $successReturn = true;
                } else {
                    $messageReturn = 'No se encontraron chatbots del cliente en estado ACTIVO en ese rango de fechas.';
                    $successReturn = false;
                }

                return response()->json([
                    'message' => $messageReturn,
                    'success' => $successReturn,
                    'customer' => [
                        'id' => $customerInfo->id,
                        'name' => $customerInfo->name,
                        'active_chatbots' => $activeChatbots,
                        'inactive_chatbots' => $inactiveChatbots
                    ]
                ]);
            }
        }
    }

    private function isValidDate($date)
    {
        return (bool)strtotime($date);
    }

    private function extractMessagesFromDrawflow($drawflow)
    {
        $messages = [];
        if (isset($drawflow['drawflow']['Home']['data'])) {
            foreach ($drawflow['drawflow']['Home']['data'] as $node) {
                if (isset($node['data']['info']['messages'])) {

                    $messages[] = $node['data']['info']['messages'];
                }
            }
        }
        return $messages;
    }

    public function recoverChatbot($id)
    {
        $rasaControl = new RasaBotControl();
        $result = $rasaControl->statusBot($id);
        return response()->json(['success' => true, 'data' => $result], 200);
    }
    public function stateChatbot($id, $state)
    {
        $rasaControl = new RasaBotControl();
        if ($state === 'active') {
            $rasaControl->startBot($id);
        } else {
            $rasaControl->stopBot($id);
        }
    }
}
