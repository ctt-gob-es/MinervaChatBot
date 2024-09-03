<?php

namespace App\Http\Controllers;

use App\Models\Lists;
use App\Models\Answers;
use App\Models\Concept;
use App\Models\Subject;
use App\Models\Synonym;
use App\Models\ListTerm;
use App\Models\Question;
use App\Models\Intentions;
use App\Models\SynonymTerm;
use Illuminate\Http\Request;
use App\Models\TermsLanguage;
use App\Models\AnswersLanguage;
use App\Models\ConceptLanguage;
use App\Models\ConceptError;
use App\Models\QuestionLanguage;
use App\Models\IntentionLanguage;
use Illuminate\Support\Facades\DB;
use App\Models\ChatbotModification;
use App\Models\Chatbot;
use Illuminate\Support\Facades\Log;


class SubjectsController extends Controller
{
    /**
     * Obtiene las temáticas relacionadas con un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getAllSubjectsApi",
     *     tags={"thematics"},
     *     summary="Obtiene las temáticas de un chatbot",
     *     @OA\Parameter(
     *         name="chatbot_id",
     *         in="query",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Temas obtenidos exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function getAllSubjects(Request $request)
    {
        try {
            $data = $request->validate([
                'chatbot_id' => 'required',
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->messages();
            $errorMessage = '';

            foreach ($errors as $field => $messagesError) {
                $errorMessage .= "El campo '$field' es obligatorio. ";
            }

            return response()->json(['success' => false, 'error' => $errorMessage], 422);
        }

        $chatbotExists = Chatbot::where('id', $data['chatbot_id'])->exists();
        if (!$chatbotExists) {
            return response()->json(['success' => false, 'message' => 'EL id de chatbot proporcionado no es valido']);
        }

        $subjects = Subject::with('creator')->where('chatbot_id', $data['chatbot_id'])->orderBy('created_at', 'DESC');

        if ($request->has('from') && $request->has('to')) {
            $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
            $subjects->whereBetween('created_at', [$request->from, $to]);
        }

        $subjects = $subjects->get();

        $subjects = $subjects->map(function ($subjectsDta) {
            return [
                'id' => $subjectsDta->id,
                'name' => $subjectsDta->name,
                'creator_name' => $subjectsDta->creator ? $subjectsDta->creator->name : 'N/A',
                'created_at' => $subjectsDta->created_at,
                'updated_at' => $subjectsDta->updated_at,
            ];
        });

        return response()->json(['success' => true, 'data' => $subjects], 200);
    }

    /**
     * Crea una nueva temática en el chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/saveSubjectsApi",
     *     summary="Crea una nueva temática",
     *     tags={"thematics"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"chatbot_id", "name"},
     *             @OA\Property(property="chatbot_id", type="string", description="ID del chatbot"),
     *             @OA\Property(property="name", type="string", description="Nombre de la temática")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Temática creada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'chatbot_id' => 'required',
            'name' => ['required', 'unique_subject_name_in_chatbot:' . $request->input('chatbot_id')],
        ]);

        $subjects = new Subject();
        $subjects->name = $request->input('name');
        $subjects->chatbot_id = $request->input('chatbot_id');
        $subjects->creator_id = auth()->id();
        $subjects->save();

        ChatbotModification::create([
            'chatbot_id' => $request->input('chatbot_id'),
            'action' => 'Temática ' . $subjects->name . ' creada',
            'user_id' => $subjects->creator_id,
        ]);

        return response()->json(['success' => true, 'message' => 'successfully.', 'data' => $subjects], 200);
    }
    /**
     * Obtiene los detalles de una temática.
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getSubjectIdApi/{id}",
     *     summary="Obtiene los detalles de una temática",
     *     tags={"thematics"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la temática a editar",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles de la temática"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Temática no encontrada"
     *     )
     * )
     */
    public function edit(string $id)
    {
        $subjects = Subject::find($id);

        if (!$subjects) {
            return response()->json(['success' => false, 'message' => 'subjects not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $subjects], 200);
    }
    /**
     * Actualiza una temática de un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \Illuminate\Http\Response
     *
     * @OA\Put(
     *     path="/api/updateSubjectsApi/{id}",
     *     tags={"thematics"},
     *     summary="Actualiza una temática de un chatbot",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la temática a actualizar",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
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
     *         name="name",
     *         in="query",
     *         description="Nuevo nombre de la temática",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Temática actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Temática actualizada exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Temática no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Temática no encontrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error de validación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error interno del servidor")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'chatbot_id' => 'required',
            'name' => ['required', 'unique_subject_name_in_chatbot:' . $request->input('chatbot_id')],
        ]);

        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Temática no encontrado'], 404);
        }

        $subject->name = $request->input('name');
        $subject->creator_id = auth()->id();
        $subject->chatbot_id = $request->input('chatbot_id');
        $subject->save();

        ChatbotModification::create([
            'chatbot_id' => $request->input('chatbot_id'),
            'action' => 'Temática ' . $subject->name . ' modificada',
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Temática actualizado exitosamente'], 200);
    }

    /**
     * Elimina una temática.
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteSubjectsApi/{id}",
     *     summary="Elimina una temática",
     *     tags={"thematics"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la temática a eliminar",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Temática eliminada exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Temática no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar la temática porque tiene intenciones asociadas"
     *     )
     * )
     */

    public function destroy(string $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Subject not found'], 404);
        }

        $intentionsCount = Intentions::where('subjects_id', $id)->count();
        if ($intentionsCount > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete subject because it has associated intentions'], 422);
        }

        $subject->delete();

        ChatbotModification::create([
            'chatbot_id' => $subject->chatbot_id,
            'action' => 'Temática ' . $subject->name . ' eliminada',
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Subject deleted successfully'], 200);
    }

    /**
     * Exporta las intenciones de una temática.
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/exportIntentionsApi/{id}",
     *     summary="Exporta las intenciones de una temática",
     *     tags={"thematics"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la temática",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Intenciones exportadas exitosamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Temática no encontrada o no tiene intenciones asociadas"
     *     )
     * )
     */
    public function exportIntentions(string $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json(['success' => false, 'message' => 'Subject not found'], 404);
        }
        $intentions = Intentions::with(['intentionLanguages', 'subject', 'concepts.conceptLanguages', 'concepts.conceptErrors' , 'concepts.lists.terms',   'concepts.lists.terms.terms_lang', 'concepts.lists.terms.synonyms.synonym', 'questions.questionLanguages', 'answers.answersLanguage'])->where('subjects_id', $id)->get();

        if (!$intentions) {
            return response()->json(['success' => false, 'message' => 'Intención no encontrada'], 404);
        }

        return response()->json(['success' => true, 'data' => $intentions], 200);
    }
    /**
     * Importa intenciones a partir de datos proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/importIntentionsApi",
     *     summary="Importa intenciones",
     *     tags={"thematics"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"chatbot_id", "data"},
     *                 @OA\Property(
     *                     property="chatbot_id",
     *                     type="string",
     *                     description="ID del chatbot"
     *                 ),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         required={"name", "questions", "answers", "concepts"},
     *                         @OA\Property(
     *                             property="name",
     *                             type="string",
     *                             description="Nombre de la intención"
     *                         ),
     *                         @OA\Property(
     *                             property="questions",
     *                             type="array",
     *                             description="Preguntas asociadas a la intención",
     *                             @OA\Items(type="string")
     *                         ),
     *                         @OA\Property(
     *                             property="answers",
     *                             type="array",
     *                             description="Respuestas asociadas a la intención",
     *                             @OA\Items(type="string")
     *                         ),
     *                         @OA\Property(
     *                             property="concepts",
     *                             type="array",
     *                             description="Conceptos asociados a la intención",
     *                             @OA\Items(type="string")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Intenciones importadas exitosamente"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Algunas intenciones no se importaron"
     *     )
     * )
     */
    public function importIntentions(Request $request)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 404);

        $data = $request->validate([
            'chatbot_id' => 'required',
            'data' => 'required',
        ]);

        $errors = [];

        foreach ($data['data'] as $intentionData) {
            DB::beginTransaction();

            try {
                $subject = $this->getOrCreateSubject($intentionData, $data['chatbot_id']);
                $intention = $this->getOrCreateIntention($intentionData, $subject, $data['chatbot_id']);

                $this->processQuestions($intention, $intentionData['questions']);
                $this->processAnswers($intention, $intentionData['answers']);
                $this->processConcepts($intention, $intentionData['concepts'], $data['chatbot_id']);

                DB::commit();
            } catch (\Throwable $th) {

                array_push($errors, ['error' => $th->getMessage(), 'line' => $th->getLine()]);
                DB::rollBack();
                continue;
            }
        }

        // Verificar si hubo errores y devolver la respuesta correspondiente
        if (count($errors) > 0) {
            return response()->json(['success' => false, 'message' => 'Algunas intenciones no se importaron.'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Importación exitosa de intenciones.'], 202);
    }

    private function getOrCreateSubject($intentionData, $chatbotId)
    {
        $subject = Subject::where('name', $intentionData['subject']['name'])->where('chatbot_id', $chatbotId)->first();

        if (!$subject) {
            $subject = new Subject();
            $subject->name = $intentionData['subject']['name'];
            $subject->creator_id = auth()->id();
            $subject->chatbot_id = $chatbotId;
            $subject->save();
        }

        return $subject;
    }

    private function getOrCreateIntention($intentionData, $subject, $chatbotId)
    {
        $originalName = $intentionData['name'];
        $lowercaseName = strtolower($originalName);
        $trimmedName = trim($lowercaseName);
        $formattedName = str_replace(' ', '_', $trimmedName);

        $intention = Intentions::where('name', $intentionData['name'])->where('chatbot_id', $chatbotId)->first();

        if (!$intention) {
            $intention = new Intentions();
            $intention->name = $formattedName;
            $intention->information = $intentionData['information'];
            $intention->validated = $intentionData['validated'];
            $intention->creation_method = 'IMPORT';
            $intention->creator = auth()->id();
            $intention->subjects_id = $subject->id;
            $intention->chatbot_id = $chatbotId;
            $intention->save();
            foreach ($intentionData['intention_languages'] as $languageData) {
                IntentionLanguage::create([
                    'name' => $languageData['name'],
                    'language' => $languageData['language'],
                    'intention_id' => $intention->id
                ]);
            }
        }
        return $intention;
    }

    private function processQuestions($intention, $questionsData)
    {
        foreach ($questionsData as $questionData) {
            $newQuestion = new Question();
            $newQuestion->intentions_id = $intention->id;
            $newQuestion->save();

            foreach ($questionData['question_languages'] as $languageData) {
                $newQuestionLanguage = new QuestionLanguage();
                $newQuestionLanguage->question = $languageData['question'];
                $newQuestionLanguage->language = $languageData['language'];
                $newQuestionLanguage->question_id = $newQuestion->id;
                $newQuestionLanguage->save();
            }
        }
    }

    private function processAnswers($intention, $answersData)
    {
        foreach ($answersData as $answerData) {
            $newAnswer = new Answers();
            $newAnswer->type = $answerData['type'];
            $newAnswer->intentions_id = $intention->id;
            $newAnswer->save();

            foreach ($answerData['answers_language'] as $languageData) {
                $newAnswerLanguage = new AnswersLanguage();
                $newAnswerLanguage->answers = $languageData['answers'];
                $newAnswerLanguage->language = $languageData['language'];

                $newAnswerLanguage->answers_id = $newAnswer->id;
                $newAnswerLanguage->save();
            }
        }
    }

    private function processConcepts($intention, $conceptsData, $chatbotId)
    {
        foreach ($conceptsData as $conceptData) {
            $concept = Concept::where('name', $conceptData['name'])->where('chatbot_id', $chatbotId)->first();
            if (!$concept) {
                $concept = new Concept();
                $concept->name = $conceptData['name'];
                $concept->chatbot_id = $chatbotId;
                $concept->save();

                foreach ($conceptData['concept_languages'] as $conceptLanguageData) {
                    ConceptLanguage::create([
                        'concept_id' => $concept->id,
                        'question' => $conceptLanguageData['question'],
                        'language' => $conceptLanguageData['language']
                    ]);
                }

                foreach ($conceptData['concept_errors'] as $conceptErrorData) {
                    ConceptError::create([
                        'concept_id' => $concept->id,
                        'answer' => $conceptErrorData['answer'],
                        'language' => $conceptErrorData['language']
                    ]);
                }

                $intention->concepts()->attach($concept);
            }

            $listsIds = [];
            foreach ($conceptData['lists'] as $listData) {
                $listsIds[] = $this->processList($concept, $listData, $chatbotId);
            }

            $concept->lists()->sync($listsIds);
        }
    }

    private function processList($concept, $listData, $chatbotId)
    {
        $list = Lists::where('name', $listData['name'])->where('chatbot_id', $chatbotId)->first();

        if (!$list) {
            $list = new Lists();
            $list->name = $listData['name'];
            $list->chatbot_id = $chatbotId;
            $list->save();

            foreach ($listData['terms'] as $termData) {

                $term = new ListTerm();
                $term->list_id = $list->id;
                $term->term = $termData['term'];
                $term->save();

                foreach ($termData['terms_lang'] as $language => $data) {
                    TermsLanguage::create([
                        'language' => $data['language'],
                        'lang_term' => $data['lang_term'],
                        'list_term_id' => $term->id
                    ]);
                }

                $synonyms = $termData['synonyms'];

                foreach ($synonyms as $synonymData) {
                    $synonymTerm = $synonymData['term'];
                    $synonymLanguage = $synonymData['language'];
                    $synonym = $synonymData['synonym']['synonym'];
                    $sinonymExist = Synonym::create([
                        'synonym' => $synonym
                    ]);
                    SynonymTerm::create([
                        'synonym_id' => $sinonymExist->id,
                        'term' => $synonymTerm,
                        'language' => $synonymLanguage,
                        'term_id' => $term->id,
                    ]);
                }
            }
        }
        return $list->id;
    }
    public function exportIntentionSelect(Request $request)
    {
        $allIntentions = [];
        foreach ($request->data as $data) {
            $subject = Subject::find($data['id']);
            if ($subject) {
                $intentions = Intentions::with([
                    'intentionLanguages',
                    'subject',
                    'concepts.conceptLanguages',
                    'concepts.conceptErrors',
                    'concepts.lists.terms',
                    'concepts.lists.terms.terms_lang',
                    'concepts.lists.terms.synonyms.synonym',
                    'questions.questionLanguages',
                    'answers.answersLanguage'
                ])->where('subjects_id', $data['id'])->get();

                $allIntentions[] = $intentions;
            }
        }
        return response()->json(['success' => true, 'data' => $allIntentions], 200);
    }
}
