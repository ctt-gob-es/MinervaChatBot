<?php

namespace App\Http\Controllers;

use App\Models\Lists;
use App\Models\Chatbot;
use App\Models\Synonym;
use App\Models\ListTerm;
use App\Models\Intentions;
use App\Models\ConceptList;
use App\Models\IntentionsConcept;
use App\Models\SynonymTerm;
use Illuminate\Http\Request;
use App\Models\TermsLanguage;
use Illuminate\Support\Facades\DB;
use App\Models\ChatbotModification;
use App\Models\IntentionModification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ListController extends Controller
{
    /**
     * Obtiene las listas de términos asociadas a un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getListsApi",
     *     summary="Obtiene las listas y términos asociadas a un chatbot",
     *     tags={"lists"},
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
     *         required=false,
     *         description="Fecha de inicio (opcional)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         description="Fecha de fin (opcional)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listas de términos obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Lista de términos 1"),
     *                     @OA\Property(property="chatbot_id", type="integer", example=1),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-19 10:00:00"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-19 10:00:00"),
     *                     @OA\Property(property="terms", type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="list_id", type="integer", example=1),
     *                             @OA\Property(property="term", type="string", example="Term 1"),
     *                             @OA\Property(property="language", type="string", example="en"),
     *                             @OA\Property(property="lang_term", type="string", example="Término 1"),
     *                             @OA\Property(property="created_at", type="string", format="date-time", example="2024-04-19 10:00:00"),
     *                             @OA\Property(property="updated_at", type="string", format="date-time", example="2024-04-19 10:00:00"),
     *                             @OA\Property(property="synonyms", type="array",
     *                                 @OA\Items(
     *                                     @OA\Property(property="synonym", type="string", example="Sinónimo 1"),
     *                                     @OA\Property(property="language", type="string", example="en"),
     *                                     @OA\Property(property="id", type="integer", example=1)
     *                                 )
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado")
     *         )
     *     )
     * )
     */
    public function getLists(Request $request)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado'], 404);
        $chatbotId = $request->input('chatbot_id');
        try {
            $lists = Lists::with(['terms.terms_lang'])->where('chatbot_id', $chatbotId);

            if ($request->has('from') && $request->has('to')) {
                $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
                $lists->whereBetween('created_at', [$request->from, $to]);
            }
            $listsData = [];
            $lists = $lists->orderBy('created_at', 'desc')->get();
            foreach ($lists as $list) {
                $listData = [
                    'id' => $list->id,
                    'name' => $list->name,
                    'chatbot_id' => $list->chatbot_id,
                    'created_at' => $list->created_at,
                    'updated_at' => $list->updated_at,
                    'terms' => []
                ];
                foreach ($list->terms as $term) {
                    foreach ($term->terms_lang as $termLang) {
                        $language = $termLang->language;
                        $langTerm = $termLang->lang_term;
                        $termData = [
                            'id' => $term->id,
                            'list_id' => $term->list_id,
                            'term' => $term->term,
                            'language' => $language,
                            'lang_term' => $langTerm,
                            'created_at' => $term->created_at,
                            'updated_at' => $term->updated_at,
                            'synonyms' => []
                        ];
                        $synonyms = SynonymTerm::where('term_id', $term->id)->with('synonym')->get();
                        foreach ($synonyms as $synonymTerm) {
                            $termData['synonyms'][] = [
                                'synonym' => $synonymTerm->synonym->synonym,
                                'language' => $synonymTerm->language,
                                'id' => $synonymTerm->synonym->id,
                            ];
                        }
                        $listData['terms'][] = $termData;
                    }
                }
                $listsData[] = $listData;
            }
            return response()->json(['success' => true, 'data' => $listsData], 200);
        } catch (\Throwable $th) {
            Log::error('error', ['message' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }


    /**
     * Almacena una nueva lista con términos y sinónimos.
     *
     * Este método crea una nueva lista con términos y sinónimos asociados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/createListsApi",
     *     tags={"lists"},
     *     summary="Crear una nueva lista con términos y sinónimos.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la lista a crear.",
     *         @OA\JsonContent(
     *             required={"name_list", "chatbot_id", "list"},
     *             @OA\Property(property="name_list", type="string", example="Lista de ejemplo"),
     *             @OA\Property(property="chatbot_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="list",
     *                 type="array",
     *                 @OA\Items(
     *                     required={"term", "lang"},
     *                     @OA\Property(property="term", type="string", example="Término"),
     *                     @OA\Property(property="lang", type="object",
     *                         @OA\AdditionalProperties(
     *                             type="object",
     *                             @OA\Property(property="value", type="string", example="Traducción")
     *                         )
     *                     ),
     *                     @OA\Property(property="synonyms", type="object",
     *                         @OA\AdditionalProperties(
     *                             type="object",
     *                             @OA\Property(property="value", type="array",
     *                                 @OA\Items(type="string", example="Sinónimo")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lista creada exitosamente."
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
    public function store(Request $request)
    {

        $rules = [
            'chatbot_id' => 'required|string',
            'list' => 'required',
            'name_list' => 'required|string',  // Validar que 'question' es requerido y es un objeto
        ];

        $messagesRules = [
            'chatbot_id.required' => 'El dato "chatbot_id" es obligatorio.',
            'list.required' => 'El dato "list" es obligatorio.',
            'name_list.required' => 'El dato "name_list" es obligatorio.',
        ];

        $validator = Validator::make($request->all(), $rules, $messagesRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->all(),
            ], 400);
        }

        $chatbotExists = Chatbot::where('id', $request->chatbot_id)->exists();
        if (!$chatbotExists) {
            return response()->json(['success' => false, 'message' => 'el id de chatbot proporcionado no es valido.']);
        }

        $listExists = Lists::where([['name', $request->name_list], ['chatbot_id', $request->chatbot_id]])->exists();
        if ($listExists) {
            return response()->json([
                'message' => 'Ya existe una lista: ' . $request->name_list, 'success' => false
            ], 400);
        }

        DB::beginTransaction();
        try {

            $list = Lists::create([
                'name' => $request->name_list,
                'chatbot_id' => $request->chatbot_id,
            ]);

            ChatbotModification::create([
                'chatbot_id' => $list->chatbot_id,
                'action' => 'Lista ' . $list->name . ' creada',
                'user_id' => auth()->id(),
            ]);

            if (is_string($request->list)) {
                $listAdd = json_decode($request->list, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error al decodificar JSON', 'success' => false
                    ], 400);
                }
            } else {
                $listAdd = $request->list;
            }

            foreach ($listAdd as $listT) {
                $term = $listT['term'];
                $languages = $listT['lang'];
                $lisTerm = ListTerm::create([
                    'list_id' => $list->id,
                    'term' => $term,
                ]);
                foreach ($languages as $language => $data) {
                    TermsLanguage::create([
                        'language' => $language,
                        'lang_term' => $data['value'],
                        'list_term_id' => $lisTerm->id
                    ]);
                }
                if (!empty($listT['synonyms'])) {
                    foreach ($listT['synonyms'] as $language => $synonymData) {
                        foreach ($synonymData['value'] as $synonymValue) {
                            $synonym = Synonym::create([
                                'synonym' => $synonymValue
                            ]);
                            SynonymTerm::create([
                                'synonym_id' => $synonym->id,
                                'term' => $term,
                                'language' => $language,
                                'term_id' => $lisTerm->id,
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return response()->json([
                'message' => 'Lista creada exitosamente!', 'success' => true, 'data' => $list
            ], 201);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            DB::rollBack();
        }
    }


    /**
     * Actualiza una lista existente con nuevos términos y sinónimos.
     *
     * Este método actualiza una lista existente con nuevos términos y sinónimos asociados.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID de la lista a actualizar
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/updateListsApi/{id}",
     *     tags={"lists"},
     *     summary="Actualizar una lista existente con nuevos términos y sinónimos.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la lista a actualizar, en la Url.",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la lista a actualizar.",
     *         @OA\JsonContent(
     *             required={"name_list", "list", "chatbot_id"},
     *             @OA\Property(property="name_list", type="string", example="Lista de ejemplo editada"),
     *             @OA\Property(property="list", type="string", example="[{'term':'terminoq','lang':{'castellano':{'value':'castellano'}}}]"),
     *             @OA\Property(property="chatbot_id", type="string", example="b46c09c0-f785-4aca-a5f6-f29158ce3dac")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista actualizada exitosamente."
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en los datos de entrada."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="La lista no fue encontrada."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor."
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'list' => 'required', // Validar que 'question' es requerido y es un objeto
        ];

        $messagesRules = [
            'list.required' => 'Por favor incluye la lista de terminos asociados a la lista.',
        ];

        $validator = Validator::make($request->all(), $rules, $messagesRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->all(),
            ], 400);
        }

        $listExist = Lists::where('id', $id)->exists();
        if (!$listExist) {
            return response()->json(['success' => false, 'message' => 'El id de lista proporcionado no es valido.']);
        }
        $listExists = Lists::where([['name', $request->name_list], ['chatbot_id', $request->chatbot_id], ['id', '!=', $id]])->exists();
        if ($listExists) {
            return response()->json([
                'message' => 'Ya existe una lista: ' . $request->name_list, 'success' => false
            ], 400);
        }
        try {
            $list = Lists::where('id', $id)->first();

            if (isset($request->name_list)) {
                $list->name = $request->name_list;
            }
            $list->save();

            if ($list) {
                ChatbotModification::create([
                    'chatbot_id' => $list->chatbot_id,
                    'action' => 'Lista ' . $list->name . ' modificada',
                    'user_id' => auth()->id(),
                ]);
            }

            $conceptIds = ConceptList::where('list_id', $id)->pluck('concept_id');
            $intentionsIds = IntentionsConcept::where('concept_id', $id)->pluck('intention_id');

            foreach ($intentionsIds as $intId) {
                IntentionModification::create([
                    'intention_id' => $intId,
                    'action' => 'Lista ' . $list->name . ' asociado a la intencion, fue modificada.',
                    'user_id' => auth()->id(),
                ]);
            }

            $listTer = ListTerm::where('list_id', $id)->get();

            foreach ($listTer as $lstT) {
                TermsLanguage::where('list_term_id', $lstT['id'])->delete();
                $st = SynonymTerm::where('term_id', $lstT['id'])->get();
                if (count($st) > 0) {
                    foreach ($st as $s) {
                        SynonymTerm::where('term_id', $lstT['id'])->delete();
                        Synonym::where('id', $s['synonym_id'])->delete();
                    }
                }
            }
            ListTerm::where('list_id', $id)->delete();

            if (is_string($request->list)) {
                $listAdd = json_decode($request->list, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error al decodificar JSON', 'success' => false
                    ], 400);
                }
            } else {
                $listAdd = $request->list;
            }

            foreach ($listAdd as $listT) {
                if (isset($listT['term'])) {
                    $term = $listT['term'];
                } else {
                    return response()->json(['success' => false, 'message' => 'Por favor, cada elemento de "list" debe tener un "term"']);
                }
                $languages = $listT['lang'];
                $lisTerm = ListTerm::create([
                    'list_id' => $list->id,
                    'term' => $term,
                ]);
                foreach ($languages as $language => $data) {
                    TermsLanguage::create([
                        'language' => $language,
                        'lang_term' => $data['value'],
                        'list_term_id' => $lisTerm->id
                    ]);
                }
                if (!empty($listT['synonyms'])) {
                    foreach ($listT['synonyms'] as $language => $synonymData) {
                        foreach ($synonymData['value'] as $synonymValue) {
                            $synonym = Synonym::create([
                                'synonym' => $synonymValue
                            ]);
                            SynonymTerm::create([
                                'synonym_id' => $synonym->id,
                                'term' => $term,
                                'language' => $language,
                                'term_id' => $lisTerm->id,
                            ]);
                        }
                    }
                }
            }
            $intentions = Intentions::where('chatbot_id', $list->chatbot_id)->get();
            foreach ($intentions as $intention) {
                Intentions::where('id', $intention->id)->update([
                    'training' => true
                ]);
            }
            return response()->json([
                'message' => 'Lista actualizada exitosamente!', 'success' => true
            ], 201);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
        }
    }

    /**
     * Elimina una lista y términos existentes.
     *
     * @param int $id ID de la lista a eliminar
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteListsApi/{id}",
     *     summary="Elimina una lista y términos existentes",
     *     tags={"lists"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la lista a eliminar, en la URL.",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Lista eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Lista eliminada."),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar la lista porque ya está asociada a un concepto",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="La lista no se puede eliminar porque ya está asociada a un concepto."),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al eliminar la lista",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $listExist = ConceptList::where('list_id', $id)->exists();



            if (!$listExist) {
                $listFound = Lists::where('id', $id)->with(['terms'])->first();
                if (!$listFound) {
                    return response()->json(['success' => false, 'message' => 'El id proporcionado no es valido para ninguna lista.']);
                }
                foreach ($listFound->terms as $term) {
                    $synonymTerms = SynonymTerm::where('term_id', $term->id)->get();

                    foreach ($synonymTerms as $synonymTerm) {
                        $synonym = Synonym::where('id', $synonymTerm->synonym_id);
                        if ($synonym) {
                            $synonym->delete();
                        }
                        $synonymTerm->delete();
                    }
                }
                $listTer = ListTerm::where('list_id', $id)->get();
                foreach ($listTer as $lstT) {
                    TermsLanguage::where('list_term_id', $lstT['id'])->delete();
                }
                $listTer = ListTerm::where('list_id', $id)->get();
                $listFound->delete();

                ChatbotModification::create([
                    'chatbot_id' => $listFound->chatbot_id,
                    'action' => 'Lista ' . $listFound->name . ' eliminada',
                    'user_id' => auth()->id(),
                ]);
                return response()->json([
                    'message' => 'Lista eliminada.', 'success' => true
                ], 201);
            } else {
                return response()->json([
                    'message' => 'La lista no se puede eliminar porque ya está asociada a un concepto.', 'success' => false
                ], 422);
            }
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
        }
    }
}
