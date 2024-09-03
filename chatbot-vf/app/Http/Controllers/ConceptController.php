<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\Concept;
use App\Models\Intentions;
use App\Models\ConceptList;
use App\Models\Lists;
use Illuminate\Http\Request;
use App\Models\ConceptLanguage;
use App\Models\ConceptError;
use App\Models\IntentionsConcept;
use App\Models\IntentionModification;
use App\Models\ChatbotModification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConceptController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index()
    {
        $conceptos = Concept::all();
        return view('concepts.index', compact('concepts'));
    }

    /**
     * Obtiene los contextos asociados a un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getContextsApi",
     *     summary="Obtiene los contextos asociados a un chatbot",
     *     tags={"contexts"},
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
     *         description="Fecha de inicio del filtro (Formato: YYYY-MM-DD)(opcional)"
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha de fin del filtro (Formato: YYYY-MM-DD)(opcional)"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contextos obtenidos exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     @OA\Property(
     *                         property="name",
     *                         type="string",
     *                         example="Contexto 1"
     *                     ),
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado"
     *     )
     * )
     */
    public function getConcepts(Request $request)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado'], 404);
        $chatbotId = $request->input('chatbot_id');
        try {
            $concepts = Concept::with(['lists.terms', 'conceptLanguages', 'conceptErrors'])
                ->where('chatbot_id', $chatbotId);
            if ($request->has('from') && $request->has('to')) {
                $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
                $concepts->whereBetween('created_at', [$request->from, $to]);
            }
            $concepts->orderBy('created_at', 'desc');

            $concepts = $concepts->get();
            return response()->json(['success' => true, 'data' => $concepts], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['message' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('concepts.create');
    }

    /**
     * Almacena un nuevo contexto.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/createContextsApi",
     *     summary="Almacena un nuevo contexto",
     *     tags={"contexts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"concept", "chatbot_id", "question"},
     *             @OA\Property(property="concept", type="object",
     *                 required={"name", "lists"},
     *                 @OA\Property(property="name", type="string", description="Nombre del contexto"),
     *                 @OA\Property(property="lists", type="array",
     *                     @OA\Items(type="object",
     *                         required={"id"},
     *                         @OA\Property(property="id", type="integer", description="ID de la lista asociada al contexto")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="chatbot_id", type="string", description="ID del chatbot asociado al contexto"),
     *             @OA\Property(property="question", type="string", description="Pregunta asociada al contexto en varios idiomas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contexto creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Se creó un nuevo contexto.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de entrada no válidos",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Errores de validación."),
     *             @OA\Property(property="errors", type="object", additionalProperties={"type"="string"})
     *         )
     *     ),
     *     @OA\SecurityScheme(
     *         securityScheme="bearerAuth",
     *         type="http",
     *         scheme="bearer",
     *         bearerFormat="JWT"
     *     )
     * )
     */
    public function store(Request $request)
    {

            $rules = [
                'chatbot_id' => 'required|string',
                'concept.name' => 'required|string',
                'question' => 'required',  // Validar que 'question' es requerido y es un objeto
                'concept.lists' => 'required|array', // Validar que 'lists' es un array, si está presente
                'error' => 'required'
            ];

            $messagesRules = [
                'chatbot_id.required' => 'El dato "chatbot_id" es obligatorio.',
                'concept.name.required' => 'El dato "concept.name" es obligatorio.',
                'question.required' => 'El dato "question" es obligatorio. Debe ser de tipo array',
                'error.required' => 'El dato "error" es obligatorio. Debe ser de tipo array',
                'concept.lists.required' => 'El dato "concept.lists" es obligatorio.'
            ];


        $validator = Validator::make($request->all(), $rules, $messagesRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->all(),
            ], 400);
        }

        DB::beginTransaction();

        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 404);

        $data = $request->all();
        try {
            $chatbotExist = Chatbot::where('id', $data['chatbot_id'])->exists();

            if(!$chatbotExist){
                return response()->json(['success' => false, 'message' => 'El id de chatbot no es valido']);
            }
            $conceptExists = Concept::where([['name', $data['concept']['name']], ['chatbot_id', $data['chatbot_id']]])->exists();
            if ($conceptExists) {
                return response()->json([
                    'message' => 'Ya existe un concepto: ' . $data['concept']['name'], 'success' => false
                ], 400);
            }

            $newConcept = Concept::create([
                'name' => $data['concept']['name'],
                'chatbot_id' => $data['chatbot_id']
            ]);

            ChatbotModification::create([
                'chatbot_id' => $data['chatbot_id'],
                'action' => 'Contexto ' . $data['concept']['name'] . ' creado',
                'user_id' => auth()->id(),
            ]);

            if (is_string($data["question"])) {
            $question = json_decode($data["question"], true);
            } else {
                $question = $data["question"];
            }


            foreach ($question as $lang => $value) {
                ConceptLanguage::create([
                    'concept_id' => $newConcept->id,
                    'question' => $value['value'],
                    'language' => $lang
                ]);
            }

            if (is_string($data["error"])) {
                $errorAns = json_decode($data["error"], true);
                } else {
                    $errorAns = $data["error"];
                }


                foreach ($errorAns as $langErr => $value) {
                    ConceptError::create([
                        'concept_id' => $newConcept->id,
                        'answer' => $value['value'],
                        'language' => $langErr
                    ]);
                }

            if (count($data['concept']['lists']) > 0 && count($data['concept']['lists']) < 2) {
                foreach ($data['concept']['lists'] as $key => $list) {
                    if(isset($list['id'])){

                        $listExist = Lists::where('id', $list['id'])->exists();
                        if($listExist){

                            $listName = Lists::where('id', $list['id'])->value('name');
                            if($listName != $data['concept']['name']){
                                return response()->json(['success' => false, 'message' => 'El nombre del concepto debe coincidir con el nombre de la lista que quieras asociar. Nombre de lista: '.$listName]);
                            }

                            $newConcept->lists()->attach($list['id']);
                        } else {
                            return response()->json(['success' => false, 'message' => 'El id: '.$list['id'].'. No es un id valido para Listas.']);
                        }
                    } else {
                        return response()->json(['success' => false, 'message' => 'Los elementos del array "lists" deben tener la propiedad "id"']);
                    }
                }
            } else {
                return response()->json(['success'=> false, 'message' => 'Lo siento, solo puedes asociar un contexto a una lista. Por favor, debes mantener la estructura de array "lists" pero solo con un elemento.']);
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Se creó un nuevo contexto.', 'data'=> $newConcept], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $concept = Concept::findOrFail($id);
        return view('concepts.show', compact('concept'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $concept = Concept::findOrFail($id);
        return view('concepts.edit', compact('concept'));
    }

    /**
     * Actualiza un contexto existente.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID del contexto a actualizar
     * @return \Illuminate\Http\Response
     *
     * @OA\Put(
     *     path="/api/updateContextsApi/{id}",
     *     summary="Actualiza un contexto existente",
     *     tags={"contexts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del contexto a actualizar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"concept", "question"},
     *             @OA\Property(property="concept", type="object",
     *                 required={"name", "lists"},
     *                 @OA\Property(property="name", type="string", description="Nombre del contexto"),
     *                 @OA\Property(property="lists", type="array",
     *                     @OA\Items(type="object",
     *                         required={"id"},
     *                         @OA\Property(property="id", type="integer", description="ID de la lista asociada al contexto")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="question", type="string", description="Pregunta asociada al contexto en varios idiomas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Contexto actualizado exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ocurrió un error inesperado"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {

        DB::beginTransaction();

        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 404);

        try {

            $data = $request->all();
            $conceptFound = Concept::where('id', $id)->with('lists')->first();

            if (!$conceptFound) {
                return $errorMessage;
            }
            $conceptExists = Concept::where([['name', $data['concept']['name']], ['chatbot_id', $conceptFound->chatbot_id], ['id', '!=', $conceptFound->id]])->exists();
            if ($conceptExists) {
                return response()->json([
                    'message' => 'Ya existe un concepto: ' . $data['concept']['name'], 'success' => false
                ], 400);
            }
            if (isset($data['concept']['name'])) {
            $conceptFound->name = $data['concept']['name'];
            }

            if (isset($data['concept']['lists'])) {
                if(count($data['concept']['lists']) > 1){
                    return response()->json(['success'=> false, 'message' => 'Lo siento, solo puedes asociar un contexto a una lista. Por favor, debes mantener la estructura de array "lists" pero solo con un elemento.']);
                }

                foreach ($data['concept']['lists'] as $list) {
                    if (!isset($list['id'])) {
                        return response()->json(['success' => false, 'message' => 'Por favor, el array concept lists debe tener objetos con la propiedad id']);
                    }
                }

                $listsIds = collect($data['concept']['lists'])->pluck('id')->toArray();
                foreach($listsIds as $listId){
                    $listExists = Lists::where('id', $listId)->exists();
                    if(!$listExists){
                        return response()->json(['success' => false, 'message' => 'El id: '.$listId.'. No es valido para ninguna lista']);
                    }
                    $listName = Lists::where('id', $listId)->value('name');
                    if($listName != $data['concept']['name']){
                        return response()->json(['success' => false, 'message' => 'El nombre del concepto debe coincidir con el nombre de la lista que quieras asociar. Nombre de lista: '.$listName]);
                    }
                }
            $conceptFound->lists()->sync($listsIds);
            }

            $conceptFound->save();

            ChatbotModification::create([
                'chatbot_id' => $conceptFound->chatbot_id,
                'action' => 'Contexto ' . $data['concept']['name'] . ' modificado',
                'user_id' => auth()->id(),
            ]);

            $intentionIds = IntentionsConcept::where('concept_id', $id)->pluck('intention_id');

            foreach ($intentionIds as $intId) {
                IntentionModification::create([
                    'intention_id' => $intId,
                    'action' => 'Contexto ' . $data['concept']['name'] . ' asociado a la inteción, fue modificado',
                    'user_id' => auth()->id(),
                ]);
            }

            $intentions = Intentions::where('chatbot_id', $conceptFound->chatbot_id)->get();
            foreach ($intentions as $intention) {
                Intentions::where('id', $intention->id)->update([
                    'training' => true
                ]);
            }

            if (isset($data["question"])) {
            ConceptLanguage::where('concept_id', $id)->delete();

            if (is_string($data["question"])) {
                $question = json_decode($data["question"], true);
            } else {
                $question = $data["question"];
            }

            foreach ($question as $lang => $value) {
                ConceptLanguage::create([
                    'concept_id' => $id,
                    'question' => $value['value'],
                    'language' => $lang
                ]);
            }
            }

            if (isset($data["error"])) {
                ConceptError::where('concept_id', $id)->delete();

                if (is_string($data["error"])) {
                    $errorAns = json_decode($data["error"], true);
                } else {
                    $errorAns = $data["error"];
                }

                foreach ($errorAns as $langErr => $value) {
                    ConceptError::create([
                        'concept_id' => $id,
                        'answer' => $value['value'],
                        'language' => $langErr
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Se actualizó el contexto seleccionado.'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Elimina un contexto existente.
     *
     * @param int $id ID del contexto a eliminar
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteContextsApi/{id}",
     *     summary="Elimina un contexto existente",
     *     tags={"contexts"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del contexto a eliminar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contexto eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="contexto eliminado exitosamente!"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El contexto no se puede eliminar porque tiene intenciones asociadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El contexto no se puede eliminar porque tiene intenciones asociadas"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {

        $idConcept = Concept::where('id', $id)->exists();
        if(!$idConcept){
            return response()->json(['success' => false, 'message' => 'El id proporcionado no corresponde a un contexto valido.']);
        }

        $int_concepts = IntentionsConcept::where('concept_id', $id)->exists();

        if ($int_concepts) {
            return response()->json([
                'message' => 'El contexto no se puede eliminar porque tiene intenciones asociadas.', 'success' => false
            ], 422);
        } else {
            ConceptLanguage::where('concept_id', $id)->delete();
            ConceptError::where('concept_id', $id)->delete();
            ConceptList::where('concept_id', $id)->delete();
            $concept = Concept::where('id', $id)->first();

            if ($concept) {
                $concept->delete();
                ChatbotModification::create([
                    'chatbot_id' => $concept->chatbot_id,
                    'action' => 'Contexto ' . $concept->name . ' eliminado',
                    'user_id' => auth()->id(),
                ]);
            }
            return response()->json([
                'message' => 'Contexto eliminado exitosamente!', 'success' => true
            ], 201);
        }
    }
}
