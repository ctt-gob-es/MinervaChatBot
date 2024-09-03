<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use GuzzleHttp\Client;
use App\Models\ChatbotPort;
use Illuminate\Http\Request;
use App\Models\Intentions;
use Illuminate\Support\Facades\Log;
use App\Models\SupervisedManual;
use App\Models\Question;
use App\Models\QuestionLanguage;
use Illuminate\Validation\ValidationException;

class ManualTrainingController extends Controller
{
    /**
     * Obtiene el listado de preguntas en entrenamiento manual.
     *
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/resourceManualTrainingApi",
     *     tags={"manualTraining"},
     *     summary="Obtener preguntas",
     *     @OA\Parameter(
     *         name="Q",
     *         in="query",
     *         required=true,
     *         description="Tipo de consulta",
     *         @OA\Schema(type="integer", enum={0, 1})
     *     ),
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
     *         description="Fecha de inicio (YYYY-MM-DD)(opcional)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         required=false,
     *         description="Fecha de fin (YYYY-MM-DD)(opcional)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Éxito al obtener las conversaciones",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="question", type="string"),
     *                 @OA\Property(property="intention_id", type="integer"),
     *                 @OA\Property(property="intention", type="string"),
     *                 @OA\Property(property="subjects_id", type="integer"),
     *                 @OA\Property(property="language", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud, parámetros incorrectos o faltantes"
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

        if($_GET['Q'] != 0 && $_GET['Q'] != 1){
            return response()->json(['success'=>false, 'message'=>'Por favor, el parametro Q debe ser 0 o 1. 0 para listar Con intención y 1 Sin intención.']);
        }

        switch ($_GET['Q']) {
            case 0:
                $query = SupervisedManual::join('intentions', 'intentions.id', 'supervised_manual.intention_id')
                    ->where([['supervised_manual.chatbot_id', $_GET['chatbot_id']], ['supervised_manual.manual_rating', null]])
                    ->whereNotIn('intentions.name', [
                        'FORMULARIO_TERMINADO', 'desvio_agente', 'mood_great', 'bot_challenge', 'cancelar',
                        'no_le_he_entendido', 'greet', 'affirm', 'goodbye', 'deny', 'mood_unhappy'
                    ])
                    ->select(
                        'supervised_manual.id',
                        'supervised_manual.question',
                        'intentions.id as intention_id',
                        'intentions.name as intention',
                        'intentions.subjects_id',
                        'supervised_manual.language',
                        'supervised_manual.created_at'
                    );

                if (isset($_GET['from']) && isset($_GET['to']) && $_GET['from'] !== 'null' && $_GET['to'] !== 'null') {
                    $to = date('Y-m-d', strtotime($_GET['to'] . ' +1 day'));
                    $query->whereBetween('supervised_manual.created_at', [$_GET['from'], $to]);
                }
                $data = $query->orderByDesc('supervised_manual.created_at')->get();
                break;
            case 1:
                $query = SupervisedManual::where([['manual_rating', null], ['intention_id', null], ['chatbot_id', $_GET['chatbot_id']]])
                    ->select(
                        'id',
                        'question',
                        'language',
                        'created_at'
                    );

                if (isset($_GET['from']) && isset($_GET['to']) && $_GET['from'] !== 'null' && $_GET['to'] !== 'null') {
                    $to = date('Y-m-d', strtotime($_GET['to'] . ' +1 day'));
                    $query->whereBetween('created_at', [$_GET['from'], $to]);
                }
                $data = $query->orderByDesc('created_at')->get();
                break;
        }
        return response()->json($data);
    }



    /**
     * Almacena registros importados desde un archivo Excel.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/importCsvApi",
     *     tags={"manualTraining"},
     *     summary="Almacenar registros desde un archivo Excel",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Archivo Excel a importar",
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="xlsx_import",
     *                     description="Archivo Excel",
     *                     type="string",
     *                     format="file"
     *                 ),
     *                 required={"xlsx_import"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Éxito al importar los registros"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en la solicitud, archivo Excel no proporcionado o incorrecto"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación, archivo Excel vacío o celdas vacías"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $basePath = config('app.url');
        $fileExcel = $request->file('xlsx_import');
        $fileNameE = $fileExcel->getClientOriginalName();

        if ($fileExcel->getClientOriginalExtension() == 'xls' or $fileExcel->getClientOriginalExtension() == 'xlsx') {
            $fileExcel->move(public_path('support/tmp_csv'), $fileNameE);
            $routeExcel = getcwd() . "/support/tmp_csv/" . $fileNameE;

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($routeExcel);
            $sheet = $spreadsheet->getSheet(0);

            foreach ($sheet->getRowIterator() as $row) {
                $cellInteractor = $row->getCellIterator('A', 'B');
                $cellInteractor->setIterateOnlyExistingCells(false);
                $datarow = (object)array('question' => null, 'language' => null);

                foreach ($cellInteractor as $cell) {
                    $column = $cell->getColumn();
                    $rowVal = $cell->getRow();

                    if ($rowVal > 1) {
                        switch ($column) {
                            case 'A':
                                $datarow->question = $cell->getCalculatedValue();
                                break;
                            case 'B':
                                $datarow->language = $cell->getCalculatedValue();
                                break;
                        }
                    }
                }
                if ($rowVal > 1) {
                    if (
                        is_null($datarow->question) &&
                        is_null($datarow->language)
                    ) {
                    } else {
                        $dataImport[] = $datarow;
                    }
                }
            }
            if (isset($dataImport) && is_array($dataImport)) {
                foreach ($dataImport as $key => $data) {
                    if (is_null($data->question)) {
                        return response()->json(['message' => 'Alguno de los elementos en las columna PREGUNTA es vacío']);
                    } elseif (is_null($data->language)) {
                        return response()->json(['message' => 'Alguno de los elementos en las columna IDIOMA es vacío']);
                    } else {
                        switch ($data->language) {
                            case "valenciano":
                            case "castellano":
                            case "ingles":
                                break;
                            default:
                                return response()->json(['message' => 'El idioma no es válido. Solo se admiten valenciano, castellano e ingles']);
                        }
                    }
                    $chatbotExists = Chatbot::where('id', $request->chatbot_id)->exists();
                    if(!$chatbotExists){
                        return response()->json(['success'=>false, 'message'=>'El id proporcionado para chatbot no es valido.']);
                    }
                    $chatbot =  Chatbot::where([['id', $request->chatbot_id], ['active', 1]])->first();
                    if ($chatbot !== null) {
                        $port = ChatbotPort::where([['chatbots_id', $request->chatbot_id], ['language', $data->language]])->first();
                        if ($port !== null) {
                            $api = $basePath.':' . $port->port . '/model/parse';
                            $client = new Client();
                            try {
                                $response = $client->post($api, [
                                    'json' => [
                                        'text' => $data->question,
                                        'message_id' => $key . date('Y-m-d H:i:s')
                                    ]
                                ]);
                                $dataApi = $response->getBody()->getContents();
                                $parsedData = json_decode($dataApi, true);

                                $intentName = $parsedData['intent']['name'];
                                if ($response->getBody()->getContents() !== []) {
                                    if (
                                        $intentName === 'cancelar' || $intentName === 'nlu_fallback' || $intentName === 'FORMULARIO_TERMINADO' ||
                                        $intentName === 'desvio_agente' || $intentName === 'mood_great' || $intentName === 'bot_challenge' || $intentName === 'greet' ||
                                        $intentName === 'affirm' || $intentName === 'goodbye' || $intentName === 'deny' || $intentName === 'mood_great' || $intentName === 'mood_unhappy'
                                    ) {
                                        $exist = SupervisedManual::where([['question', $data->question], ['chatbot_id', $request->chatbot_id]])->exists();
                                        if (!$exist) {
                                            SupervisedManual::create([
                                                'chatbot_id' => $request->chatbot_id,
                                                'question' => $data->question,
                                                'language' => $data->language,
                                            ]);
                                        }
                                    } else {
                                        $inten = Intentions::where([['name', $intentName], ['chatbot_id', $request->chatbot_id]])->first();
                                        if ($inten !== null) {
                                            $exist = SupervisedManual::where([['question', $data->question], ['chatbot_id', $request->chatbot_id]])->exists();
                                            if (!$exist) {
                                                SupervisedManual::create([
                                                    'chatbot_id' => $request->chatbot_id,
                                                    'question' => $data->question,
                                                    'language' => $data->language,
                                                    'intention_id' => $inten->id
                                                ]);
                                            }
                                        } else {
                                            $exist = SupervisedManual::where([['question', $data->question], ['chatbot_id', $request->chatbot_id]])->exists();
                                            if (!$exist) {
                                                SupervisedManual::create([
                                                    'chatbot_id' => $request->chatbot_id,
                                                    'question' => $data->question,
                                                    'language' => $data->language,
                                                ]);
                                            }
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::info('error', ['error' => $e]);
                                return response()->json(['error' => $e->getMessage()], 500);
                            }
                        }
                    } else {
                        return response()->json(['message' => 'El chatbot se encuentra desactivado, verifica']);
                    }
                }
            } else {
                return response()->json(['message' => 'El archivo está vacío o alguna de las filas, verifica']);
            }
            unlink($routeExcel);
        } else {
            return response()->json(['message' => 'El archivo cargado debe ser de tipo Excel, usa la plantilla']);
        }
        return response()->json(['message' => 'ok']);
    }
    /**
     * Almacena una nueva pregunta en la base de datos.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/setRatingManualApi",
     *     tags={"manualTraining"},
     *     summary="Almacena una nueva pregunta en la base de datos",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id", "intention_id", "question"},
     *             @OA\Property(
     *                 property="id",
     *                 type="integer",
     *                 description="ID de la supervisión manual"
     *             ),
     *             @OA\Property(
     *                 property="intention_id",
     *                 type="integer",
     *                 description="ID de la intención"
     *             ),
     *             @OA\Property(
     *                 property="question",
     *                 type="object",
     *                 description="Objeto JSON con preguntas en diferentes idiomas",
     *                 @OA\AdditionalProperties(
     *                     @OA\Property(
     *                         property="value",
     *                         type="string",
     *                         description="Texto de la pregunta"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rating guardado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Rating guardado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Datos inválidos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error interno del servidor")
     *         )
     *     )
     * )
     */
    public function setRatingManual(Request $request)
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

        $supervisedExists = SupervisedManual::where('id', $request->id)->exists();
        if(!$supervisedExists){
            return response()->json(['success' => false, 'message' => 'El id proporcionado para entrenamiento manual no es valido.']);
        }

        SupervisedManual::where('id', $request->id)->update([
            'manual_rating' => 'Descartada'
        ]);
        return response()->json(['success' => true, 'message' => 'Rating guardado'], 200);
    }
    /**
     * Actualiza la calificación manual de preguntas a "Descartada".
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/descartRatingManualApi",
     *     tags={"manualTraining"},
     *     summary="Actualiza la calificación manual de preguntas a 'Descartada'",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"data"},
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array de objetos con IDs de supervisiones manuales",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="id",
     *                         type="integer",
     *                         description="ID de la supervisión manual"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Calificaciones actualizadas a 'Descartada'",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Descartada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Datos inválidos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error interno del servidor")
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        foreach ($request->data as $rt) {
            SupervisedManual::where('id', $rt['id'])->update([
                'manual_rating' => 'Descartada'
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Descartada'], 200);
    }
}
