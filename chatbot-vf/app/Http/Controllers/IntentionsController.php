<?php

namespace App\Http\Controllers;

use App\Models\Answers;
use App\Models\Question;
use App\Models\Intentions;
use App\Models\IntentionModification;
use Illuminate\Http\Request;
use App\Models\AnswersLanguage;
use App\Models\QuestionLanguage;
use App\Models\IntentionLanguage;
use App\Models\IntentionsConcept;
use Illuminate\Support\Facades\DB;
use App\Models\ChatbotModification;
use Illuminate\Support\Facades\Log;
use App\Models\ChatbotSetting;


class IntentionsController extends Controller
{

    /**
     * Obtiene una lista de intenciones según los filtros proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getIntentionsApi",
     *     summary="Obtiene una lista de intenciones",
     *     tags={"intentions"},
     *     @OA\Parameter(
     *         name="chatbot_id",
     *         in="query",
     *         required=true,
     *         description="ID del chatbot",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Fecha de inicio del rango de tiempo (YYYY-MM-DD)(opcional)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha de fin del rango de tiempo (YYYY-MM-DD)(opcional)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de intenciones obtenida exitosamente"
     *     )
     * )
     */

    public function index(Request $request)
    {
        $chatbotId = $request->input('chatbot_id');
        $intentions = Intentions::with(['subject', 'user'])
            ->whereHas('subject', function ($query) use ($chatbotId) {
                $query->where('chatbot_id', $chatbotId);
            })
            ->orderBy('created_at', 'DESC');

        if ($request->has('from') && $request->has('to')) {
            $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
            $intentions->whereBetween('created_at', [$request->from, $to]);
        }
        $intentions = $intentions->get()
            ->map(function ($intention) {
                return [
                    'id' => $intention->id,
                    'name' => $intention->name,
                    'information' => $intention->information ? $intention->information : 'N/A',
                    'languages_id' => $intention->languages_id,
                    'subjects_id' => $intention->subjects_id,
                    'created_at' => $intention->created_at,
                    'updated_at' => $intention->updated_at,
                    'subject_name' => $intention->subject->name,
                    'training' => $intention->training,
                    'validated' => $intention->validated ? $intention->validated : 'N/A',
                    'creation_method' => $intention->creation_method ? $intention->creation_method : 'N/A',
                    'creator' => $intention->user ? $intention->user->name : 'N/A',
                    'has_concepts' => $intention->concepts->count() > 0,
                ];
            });

        return response()->json(['success' => true, 'data' => $intentions], 200);
    }

    /**
     * Almacena o actualiza una intención junto con sus preguntas y respuestas asociadas.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/saveIntentionsApi",
     *     summary="Almacena o actualiza una intención",
     *     tags={"intentions"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "subjects_id", "chatbot_id", "questions", "answers"},
     *             @OA\Property(property="name", type="string", description="Nombre de la intención"),
     *             @OA\Property(property="subjects_id", type="string", description="ID del tema asociado a la intención"),
     *             @OA\Property(property="chatbot_id", type="string", description="ID del chatbot asociado a la intención"),
     *             @OA\Property(property="questions", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", description="ID de la pregunta (opcional)"),
     *                 @OA\Property(property="question", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", description="ID del lenguaje de la pregunta (opcional)"),
     *                     @OA\Property(property="name_language", type="string", description="Nombre del lenguaje de la pregunta"),
     *                     @OA\Property(property="text", type="string", description="Texto de la pregunta")
     *                 ))
     *             )),
     *             @OA\Property(property="answers", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="string", description="ID de la respuesta (opcional)"),
     *                 @OA\Property(property="type", type="string", description="Tipo de respuesta (texto, imagen, etc.)"),
     *                 @OA\Property(property="answer", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", description="ID del lenguaje de la respuesta (opcional)"),
     *                     @OA\Property(property="name_language", type="string", description="Nombre del lenguaje de la respuesta"),
     *                     @OA\Property(property="text", type="string", description="Texto de la respuesta")
     *                 ))
     *             )),
     *             @OA\Property(property="concepts", type="array", @OA\Items(type="string"), description="IDs de los conceptos asociados a la intención"),
     *             @OA\Property(property="id", type="string", description="ID de la intención (opcional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Intención almacenada o actualizada correctamente"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al guardar la intención y preguntas"
     *     )
     * )
     */
    public function store(Request $request)
    {

        $id = $request->input('id') ?? null;
        if ($request->name !== 'no_le_he_entendido') {
            $data = $request->validate([
                'name' => 'required',
                'subjects_id' => 'required',
                'chatbot_id' => 'required',
                'questions' => 'required',
                'answers' => 'required',
                'concepts' => 'nullable',
            ]);
        } else {
            $data = $request->validate([
                'name' => 'required',
                'subjects_id' => 'required',
                'chatbot_id' => 'required',
                'answers' => 'required',
                'concepts' => 'nullable',
            ]);
        }


        DB::beginTransaction();

        try {
            if ($id === null) {
                $intention = Intentions::where('name', $data['name'])->where('chatbot_id', $data['chatbot_id'])->first();

                if (!$intention) {
                    //en crea intencion en caso de que no obtengamos id
                    $intention = new Intentions();
                    $intention->validated = 1;
                    $intention->creation_method = 'WEB';
                    $intention->creator = auth()->id();
                    $intention->name = $data['name'];
                    $intention->information = $request->input('information');
                    $intention->subjects_id = $request->input('subjects_id');
                    $intention->chatbot_id = $data['chatbot_id'];
                    $intention->save();
                    ChatbotModification::create([
                        'chatbot_id' => $intention->chatbot_id,
                        'action' => 'Intención ' . $intention->name . ' creada',
                        'user_id' => $intention->creator,
                    ]);
                    IntentionModification::create([
                        'intention_id' => $intention->id,
                        'action' => 'Intención ' . $intention->name . ' creada',
                        'user_id' => $intention->creator,
                    ]);

                    $intention_lang = json_decode($request->intention_language, true);
                    foreach ($intention_lang as $lang => $value) {
                        IntentionLanguage::create([
                            'intention_id' => $intention->id,
                            'name' => $value['value'],
                            'language' => $lang
                        ]);
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'exist', 500]);
                }
            } else {
                //cuando se obtenga el id, se valida si se han eliminado preguntas o respuestas a la intencion
                $intention = Intentions::findOrFail($id)->fresh();
                $intention->name = $data['name'];
                $intention->subjects_id = $request->input('subjects_id');
                $intention->information = $request->input('information');
                $intention->save();

                ChatbotModification::create([
                    'chatbot_id' => $intention->chatbot_id,
                    'action' => 'Intención ' . $intention->name . ' modificada',
                    'user_id' => auth()->id(),
                ]);
                IntentionModification::create([
                    'intention_id' => $intention->id,
                    'action' => 'Intención ' . $intention->name . ' modificada',
                    'user_id' => auth()->id(),
                ]);

                $existingQuestionIds = collect($intention->questions)->pluck('id')->toArray();
                $existingAnswerIds = collect($intention->answers)->pluck('id')->toArray();
                $existingConceptsIds = collect($intention->concepts)->pluck('id')->toArray();
                if (isset($data['questions'])) {
                    $newQuestionIds = collect($data['questions'])->pluck('id')->toArray();
                }
                $newAnswerIds = collect($data['answers'])->pluck('id')->toArray();

                if (isset($data['questions'])) {
                    $questionsToDelete = array_diff($existingQuestionIds, $newQuestionIds);
                    Question::whereIn('id', $questionsToDelete)->delete();
                }
                $answersToDelete = array_diff($existingAnswerIds, $newAnswerIds);

                Answers::whereIn('id', $answersToDelete)->delete();
                IntentionLanguage::where('intention_id', $id)->delete();
                $intention_lang = json_decode($request->intention_language, true);
                foreach ($intention_lang as $lang => $value) {
                    IntentionLanguage::create([
                        'intention_id' => $intention->id,
                        'name' => $value['value'],
                        'language' => $lang
                    ]);
                }
            }
            //Se modifican o crean preguntas
            if (isset($data['questions'])) {
                foreach ($data['questions'] as $questionData) {
                    $question = Question::updateOrCreate(['id' => $questionData['id'] ?? null], ['intentions_id' => $intention->id ?? null]);

                    $questionLanguagesIds = collect($questionData['question'])->pluck('id')->toArray();
                    QuestionLanguage::where('question_id', $question->id)->whereNotIn('id', $questionLanguagesIds)->delete();

                    foreach ($questionData['question'] as $languageData) {
                        QuestionLanguage::updateOrCreate(['id' => $languageData['id'] ?? null, 'question_id' => $question->id ?? null, 'language' => $languageData['name_language']], ['question' => $languageData['text']]);
                    }
                }
            }

            //Se modifican o crean respuestas
            foreach ($data['answers'] as $answerData) {
                $answer = Answers::updateOrCreate(['id' => $answerData['id'] ?? null], ['type' => $answerData['type'], 'intentions_id' => $intention->id ?? null]);

                $answerLanguagesIds = collect($answerData['answer'])->pluck('id')->toArray();
                AnswersLanguage::where('answers_id', $answer->id)->whereNotIn('id', $answerLanguagesIds)->delete();

                foreach ($answerData['answer'] as $languageData) {
                    AnswersLanguage::updateOrCreate(['id' => $languageData['id'] ?? null, 'answers_id' => $answer->id ?? null, 'language' => $languageData['name_language']], ['answers' => $languageData['text']]);
                }
            }

            $oldConceptsIds = $intention->concepts->map(function ($concept) {
                return $concept->id;
            })->toArray();
            $newConceptsIds = collect($data['concepts'])->map(function ($concept) {
                return $concept['id'];
            })->toArray();

            $differences = array_merge(array_diff($newConceptsIds, $oldConceptsIds), array_diff($oldConceptsIds, $newConceptsIds));

            $conceptModification = !empty($differences);

            if($conceptModification){
                Intentions::where('id', $intention->id)->update([
                    'training' => true
                ]);
            }
            //se sincronizan los conceptos asociados a una intención
            $conceptsIds = collect($data['concepts'])->pluck('id')->toArray();
            $intention->concepts()->sync($conceptsIds);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Intención guardado correctamente', 'data' => $intention], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if (!($e instanceof SQLException)) {
                app()->make(\App\Exceptions\Handler::class)->report($e);
            }
            return response()->json(['success' => false, 'message' => 'Error al guardar la intención y preguntas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene los detalles de una intención específica, incluyendo sus preguntas, respuestas y conceptos asociados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getDetailIntentionApi",
     *     summary="Obtiene los detalles de una intención específica",
     *     tags={"intentions"},
     *     @OA\Parameter(
     *         name="intention_id",
     *         in="query",
     *         description="ID de la intención",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la intención obtenidos correctamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Intención no encontrada"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al obtener los detalles de la intención"
     *     )
     * )
     */
    public function getDetailIntention(Request $request)
    {
        try {

            $intentionId = $request->input('intention_id');

            $intention = Intentions::with(['subject', 'user', 'intentionLanguages', 'concepts.lists.terms', 'concepts.conceptLanguages', 'concepts.conceptErrors'])
                ->find($intentionId);
            if (!$intention) {
                return response()->json(['success' => false, 'message' => 'Intención no encontrada'], 404);
            }
            $languages = ['castellano', 'ingles', 'valenciano'];
            $idiomasValidos = [];

            foreach ($languages as $language) {
                $idiomasValidos[$language] = ChatbotSetting::with('defaultTable')
                    ->withTrashed()
                    ->where('chatbot_id', $intention->chatbot_id)
                    ->whereHas('defaultTable', function ($query) use ($language) {
                        $query->whereIn('name', [$language]);
                    })
                    ->value('value');
            }

            $detailIntention = [
                'id' => $intention->id,
                'name' => $intention->name,
                'information' => $intention->information ? $intention->information : 'N/A',
                'creation_method' => $intention->creation_method ? $intention->creation_method : 'N/A',
                'creator' => $intention->user ? $intention->user->name : 'N/A',
                'created_at' => $intention->created_at,
                'subject_name' => $intention->subject->name,
                'subject_id' => $intention->subject->id,
                'questions' => $intention->questions->map(function ($question) use ($idiomasValidos) {
                    return [
                        'id' => $question->id,
                        'question' => $question->questionLanguages->filter(function ($questionLanguage) use ($idiomasValidos) {
                            // Verifica si el idioma actual está marcado como "1" en $idiomasValidos
                            return $idiomasValidos[$questionLanguage->language] == 1;
                        })->map(function ($questionLanguage) {
                            return [
                                'id' => $questionLanguage->id,
                                'language' => $questionLanguage->language,
                                'question' => $questionLanguage->question
                            ];
                        }),
                        'created_at' => $question->created_at,
                        'updated_at' => $question->updated_at
                    ];
                }),
                'answers' => $intention->answers->map(function ($answer) use ($idiomasValidos) {
                    return [
                        'id' => $answer->id,
                        'type' => $answer->type,
                        'answer' => $answer->answersLanguage->filter(function ($answerLanguage) use ($idiomasValidos) {
                            // Verifica si el idioma actual está marcado como "1" en $idiomasValidos
                            return $idiomasValidos[$answerLanguage->language] == 1;
                        })->map(function ($answerLanguage) {
                            return [
                                'id' => $answerLanguage->id,
                                'language' => $answerLanguage->language,
                                'answer' => $answerLanguage->answers
                            ];
                        }),
                        'created_at' => $answer->created_at,
                        'updated_at' => $answer->updated_at
                    ];
                }),
                'concepts' => $intention->concepts->map(function ($concept) {
                    return [
                        'id' => $concept->id,
                        'name' => $concept->name,
                        'question' => $concept->question,
                        'created_at' => $concept->created_at,
                        'updated_at' => $concept->updated_at,
                        'concept_language' => $concept->conceptLanguages->map(function ($lang) {
                            return [
                                'id' => $lang->id,
                                'question' => $lang->question,
                                'lang' => $lang->language,
                                'concept_id' => $lang->concept_id
                            ];
                        }),
                        'lists' => $concept->lists->map(function ($list) {
                            return [
                                'id' => $list->id,
                                'name' => $list->name,
                                'terms' => $list->terms->map(function ($term) {
                                    return [
                                        'id' => $term->id,
                                        'term' => $term->term,
                                    ];
                                })
                            ];
                        })
                    ];
                }),
                'intention_language' => $intention->intentionLanguages->filter(function ($intention_lang) use ($idiomasValidos) {
                    return $idiomasValidos[$intention_lang->language] == 1;
                })->map(function ($intention_lang) {
                    return [
                        'id' => $intention_lang->id,
                        'name' => $intention_lang->name,
                        'language' => $intention_lang->language,
                        'intention_id' => $intention_lang->intention_id,
                        'created_at' => $intention_lang->created_at,
                        'updated_at' => $intention_lang->updated_at
                    ];
                })
            ];

            return response()->json(['success' => true, 'data' => $detailIntention], 200);
        } catch (\Exception $e) {
            Log::info('error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Error al obtener los detalles de la intención: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina una intención existente.
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteIntentionsApi/{id}",
     *     summary="Elimina una intención existente",
     *     tags={"intentions"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la intención",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Intención eliminada correctamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="La intención no existe"
     *     )
     * )
     */
    public function delete($id)
    {
        $intention = Intentions::find($id);
        if (!$intention) {
            return response()->json(['success' => false, 'message' => 'La intención no existe.'], 404);
        }

        $intentionsConcepts = IntentionsConcept::where('intention_id', $id)->count();
        if ($intentionsConcepts > 0) {
            return response()->json(['success' => false, 'message' => 'No se puede borrar intenciones que estén asociadas a contextos'], 422);
        }

        $intention->delete();

        ChatbotModification::create([
            'chatbot_id' => $intention->chatbot_id,
            'action' => 'Intención ' . $intention->name . ' eliminada',
            'user_id' => auth()->id(),
        ]);
        IntentionModification::create([
            'intention_id' => $intention->id,
            'action' => 'Intención ' . $intention->name . ' eliminada',
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Intención eliminada correctamente.'], 200);
    }

    public function getIntentionsBuilder(Request $request)
    {
        $chatbotId = $request->input('chatbot_id');
        $intentions = Intentions::select('id', 'name')
            ->whereHas('subject', function ($query) use ($chatbotId) {
                $query->where('chatbot_id', $chatbotId);
            })
            ->whereDoesntHave('concepts')
            ->whereNotIn('name', [
                'FORMULARIO_TERMINADO', 'desvio_agente', 'mood_great', 'bot_challenge', 'cancelar',
                'no_le_he_entendido', 'greet', 'affirm', 'goodbye', 'deny', 'mood_unhappy'
            ])
            ->orderBy('created_at', 'DESC')
            ->get();
        return response()->json(['success' => true, 'data' => $intentions], 200);
    }

    /**
     * Obtiene el historial de modificaciones de una intención por su ID.
     *
     * @param string $intentionsId ID de la intención
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getHistoryIntentionsApi/{intentionsId}",
     *     tags={"intentions"},
     *     summary="Obtiene el historial de modificaciones de una intención",
     *     @OA\Parameter(
     *         name="intentionsId",
     *         in="path",
     *         description="ID de la intención",
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
     *         description="No se pudo obtener el historial de la intención"
     *     )
     * )
     */
    public function getHistoryIntentions($intentionsId)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un problema al listar el historial de la intenciòn.'], 404);
        try {

            $modifications = Intentions::where('id', $intentionsId)->with(['modifications' => function ($query) {
                $query->select('id', 'action', 'intention_id', 'user_id', 'created_at');
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
}
