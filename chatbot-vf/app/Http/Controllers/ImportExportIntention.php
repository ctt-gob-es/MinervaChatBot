<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IntentionModification;
use App\Models\Intentions;
use App\Models\ChatbotModification;
use App\Models\Subject;
use Illuminate\Support\Facades\Log;
use App\Models\IntentionLanguage;
use App\Models\Question;
use App\Models\Answers;
use App\Models\AnswersLanguage;
use App\Models\QuestionLanguage;
use App\Models\ChatbotSetting;
use Illuminate\Support\Facades\DB;

class ImportExportIntention extends Controller
{
    public function importIntentionsXlsx(Request $request)
    {
        $fileExcel = $request->file('xlsx_import');
        $fileNameE = $fileExcel->getClientOriginalName();
        if ($fileExcel->getClientOriginalExtension() == 'xls' or $fileExcel->getClientOriginalExtension() == 'xlsx') {

            $fileExcel->move(public_path('support/tmp_csv'), $fileNameE);
            $routeExcel = getcwd() . "/support/tmp_csv/" . $fileNameE;

            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($routeExcel);
            $sheet = $spreadsheet->getSheet(0);
            function allTextsAreNull($questions)
            {
                foreach ($questions as $question) {
                    if (!is_null($question['text'])) {
                        return false;
                    }
                }
                return true;
            }
            foreach ($sheet->getRowIterator() as $row) {
                $cellInteractor = $row->getCellIterator('A', 'K');
                $cellInteractor->setIterateOnlyExistingCells(false);
                $datarow = (object)array(
                    'thematic' => null,
                    'intention_name' => null,
                    'intention_language' => array(
                        'castellano' => null,
                        'ingles' => null,
                        'valenciano' => null
                    ),
                    'questions' => array(),
                    'answers' => array(),
                    'row' => null,
                );

                foreach ($cellInteractor as $cell) {
                    $column = $cell->getColumn();
                    $rowVal = $cell->getRow();

                    if ($rowVal > 1) {
                        switch ($column) {
                            case 'A':
                                $datarow->thematic = $cell->getCalculatedValue();
                                break;
                            case 'B':
                                $datarow->intention_name = $cell->getCalculatedValue();
                                break;
                            case 'C':
                                $datarow->intention_language['castellano'] = $cell->getCalculatedValue();
                                break;
                            case 'D':
                                $datarow->intention_language['ingles'] = $cell->getCalculatedValue();
                                break;
                            case 'E':
                                $datarow->intention_language['valenciano'] = $cell->getCalculatedValue();
                                break;
                            case 'F':
                            case 'G':
                            case 'H':
                                $language = '';
                                if ($column == 'F') $language = 'castellano';
                                elseif ($column == 'G') $language = 'ingles';
                                elseif ($column == 'H') $language = 'valenciano';

                                $question = array(
                                    'name_language' => $language,
                                    'text' => $cell->getCalculatedValue()
                                );
                                $datarow->questions[] = $question;
                                break;
                            case 'I':
                            case 'J':
                            case 'K':
                                $language = '';
                                if ($column == 'I') $language = 'castellano';
                                elseif ($column == 'J') $language = 'ingles';
                                elseif ($column == 'K') $language = 'valenciano';

                                $answer = array(
                                    'name_language' => $language,
                                    'text' => $cell->getCalculatedValue()
                                );
                                $datarow->answers[] = $answer;
                                break;
                        }
                        $datarow->row = $rowVal;
                    }
                }

                if ($rowVal > 1) {
                    if (
                        is_null($datarow->thematic) &&
                        is_null($datarow->intention_name) &&
                        allTextsAreNull($datarow->questions) && allTextsAreNull($datarow->answers)
                    ) {
                    } else {
                        $dataImport[] = $datarow;
                    }
                }
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
            // Función para contar la cantidad de "#" en un texto dado
            function countHashtags($text)
            {
                return substr_count($text, '#');
            }
            if (isset($dataImport) && is_array($dataImport)) {
                foreach ($dataImport as $key => $data) {

                    if (is_null($data->thematic)) {
                        return response()->json(['message' => 'El valor en la columna TEMÁTICA es vacío, verifica la fila '.$data->row]);
                    } elseif (is_null($data->intention_name)) {
                        return response()->json(['message' => 'El valor en la columna INTENCIÓN es vacío, verifica la fila '.$data->row]);
                    }
                    if (strpos($data->intention_name, ' ') !== false) {
                        return response()->json(['message' => 'La intención: ' . $data->intention_name . ' no debe contener espacios, reemplaza por _ verifica la fila '.$data->row]);
                    }
                    if (preg_match('/[A-Z]/', $data->intention_name)) {
                        return response()->json(['message' => 'La intención: ' . $data->intention_name . ' no debe contener mayúsculas, verifica la fila '.$data->row]);
                    }

                    // Verifica si alguno de los datos para los idiomas válidos es nulo
                    foreach ($idiomasValidos as $idioma => $valido) {
                        if ($valido === "1") {
                            // Primera validación para $data->intention_language
                            if (is_null($data->intention_language[$idioma])) {
                                return response()->json(['message' => "Los datos para el idioma $idioma está vacío INTENCIÓN verifica la fila ".$data->row]);
                            }
                            // Segunda validación para $data->questions
                            foreach ($data->questions as $question) {
                                if ($question['name_language'] === $idioma && is_null($question['text'])) {
                                    return response()->json(['message' => "Los datos para el idioma $idioma está vacío en PREGUNTA, verifica la fila ".$data->row]);
                                }
                            }
                            // Tercera validación para $data->answers
                            foreach ($data->answers as $answer) {
                                if ($answer['name_language'] === $idioma && is_null($answer['text'])) {
                                    return response()->json(['message' => "Los datos para el idioma $idioma está vacío en RESPUESTA, verifica la fila ".$data->row]);
                                }
                            }
                        }
                    }

                    $hashtagCountsQuestion = []; // Array para almacenar la cantidad de "#" en cada texto

                    foreach ($data->questions as $question) {
                        $text = $question['text'];
                        if (!is_null($text)) {
                            $hashtagCountsQuestion[] = countHashtags($text);
                        }
                    }


                    $hashtagCountsQuestion = array_unique($hashtagCountsQuestion); // Eliminar valores duplicados

                    if (count($hashtagCountsQuestion) > 1) {
                        // Si hay más de un valor único en $hashtagCounts, significa que no todos los textos tienen la misma cantidad de "#"
                        return response()->json(['message' => "No todas las PREGUNTAS tienen la misma cantidad en cada lenguaje, verifica la fila ".$data->row]);
                    }
                    $hashtagCountsAnswer = []; // Array para almacenar la cantidad de "#" en cada texto

                    foreach ($data->answers as $answer) {
                        $text = $answer['text'];
                        if (!is_null($text)) {
                            $hashtagCountsAnswer[] = countHashtags($text);
                        }
                    }

                    $hashtagCountsAnswer = array_unique($hashtagCountsAnswer); // Eliminar valores duplicados

                    if (count($hashtagCountsAnswer) > 1) {
                        // Si hay más de un valor único en $hashtagCounts, significa que no todos los textos tienen la misma cantidad de "#"
                        return response()->json(['message' => "No todas las RESPUESTAS tienen la misma cantidad en cada lenguaje, verifica la fila ".$data->row]);
                    }

                    try {
                        DB::beginTransaction();
                        $intention = Intentions::where('name', $data->intention_name)->where('chatbot_id', $request->chatbot_id)->first();
                        if (!$intention) {
                            $thematic = Subject::where([['name', $data->thematic], ['chatbot_id', $request->chatbot_id]])->first();
                            if (!$thematic) {
                                $thematic = Subject::create([
                                    'name' => $data->thematic,
                                    'chatbot_id' => $request->chatbot_id,
                                    'creator_id' => auth()->id()
                                ]);
                            }
                            $intention = Intentions::create([
                                'name' => $data->intention_name,
                                'validated' => 1,
                                'creation_method' => 'WEB',
                                'creator' => auth()->id(),
                                'chatbot_id' => $request->chatbot_id,
                                'subjects_id' => $thematic->id
                            ]);
                            ChatbotModification::create([
                                'chatbot_id' => $request->chatbot_id,
                                'action' => 'Intención ' . $intention->name . ' creada',
                                'user_id' => auth()->id(),
                            ]);
                            IntentionModification::create([
                                'intention_id' => $intention->id,
                                'action' => 'Intención ' . $intention->name . ' creada',
                                'user_id' => auth()->id(),
                            ]);
                        
 
                            foreach ($data->intention_language as $lang => $value) {
                                if($value !==null){
                                    IntentionLanguage::create([
                                        'intention_id' => $intention->id,
                                        'name' => $value,
                                        'language' => $lang
                                    ]);
                                }  
                            }
                        } else {
                            $thematic = Subject::updateOrCreate(
                                [
                                    'id' => $intention->subjects_id,
                                    'chatbot_id' => $request->chatbot_id
                                ],
                                [
                                    'name' => $data->thematic,
                                    'creator_id' => auth()->id()
                                ]
                            );

                            ChatbotModification::create([
                                'chatbot_id' => $request->chatbot_id,
                                'action' => 'Intención ' . $intention->name . ' creada',
                                'user_id' => auth()->id(),
                            ]);
                            IntentionModification::create([
                                'intention_id' => $intention->id,
                                'action' => 'Intención ' . $intention->name . ' creada',
                                'user_id' => auth()->id(),
                            ]);
                            Intentions::where('id', $intention->id)->update([
                                'training' => true
                            ]);

                            foreach ($data->intention_language as $lang => $value) {
                                IntentionLanguage::updateOrCreate(
                                    [
                                        'intention_id' => $intention->id,
                                        'language' => $lang
                                    ],
                                    [
                                        'name' => $value,
                                    ]
                                );
                            }
                        }
                        $newDataQuestions = [];
                        foreach ($data->questions as $item) {
                            // Verificar si el texto contiene el caracter #
                            if (strpos($item['text'], '#') !== false) {
                                // Si hay #, dividir el texto en partes
                                $separatedText = explode("#", $item['text']);
                                foreach ($separatedText as $key => $text) {
                                    $newDataQuestions[$key]['question'][] = [
                                        'name_language' => $item['name_language'],
                                        'text' => trim($text)
                                    ];
                                }
                            } else {
                                // Si no hay #, agregar una sola pregunta con los tres idiomas
                                $newDataQuestions[0]['question'][] = [
                                    'name_language' => $item['name_language'],
                                    'text' => $item['text']
                                ];
                            }
                        }
                        $newDataAnswers = [];
                        foreach ($data->answers as $item) {
                            // Verificar si el texto contiene el caracter #
                            if (strpos($item['text'], '#') !== false) {
                                // Si hay #, dividir el texto en partes
                                $separatedText = explode("#", $item['text']);
                                foreach ($separatedText as $key => $text) {
                                    $newDataAnswers[$key]['answer'][] = [
                                        'name_language' => $item['name_language'],
                                        'text' => trim($text)
                                    ];
                                }
                            } else {
                                // Si no hay #, agregar una sola pregunta con los tres idiomas
                                $newDataAnswers[0]['answer'][] = [
                                    'name_language' => $item['name_language'],
                                    'text' => $item['text']
                                ];
                            }
                        }

                        foreach ($newDataQuestions as $questionData) {
                            $validate = true;
                            if ($validate) {
                                $question = Question::updateOrCreate(['id' => $questionData['id'] ?? null], ['intentions_id' => $intention->id ?? null]);
                            }
                            foreach ($questionData['question'] as $languageData) {
                                $validate = false;
                                if ($languageData['text'] !== null) {
                                    $existQuestion = Question::with('intentions', 'questionLanguages')
                                    ->whereHas('intentions', function ($query) use ($request) {
                                        $query->where('chatbot_id', $request->chatbot_id);
                                    })
                                    ->whereHas('questionLanguages', function ($query) use ($languageData) {
                                        $query->where('question', $languageData['text'])
                                              ->where('language', $languageData['name_language']);
                                    })
                                    ->where('intentions_id', $intention->id)
                                    ->exists();

                                    if (!$existQuestion) {

                                        QuestionLanguage::updateOrCreate(
                                            [
                                                'question_id' => $question->id,
                                                'question' => $languageData['text'],
                                                'language' => $languageData['name_language']
                                            ]
                                        );
                                    } else {
                                        break;
                                    }
                                }
                            }
                            $existQ = QuestionLanguage::where('question_id', $question->id)->exists();
                            if (!$existQ) {
                                Question::where('id', $question->id)->delete();
                            }
                        }

                        foreach ($newDataAnswers as $answerData) {
                            $validate = true;
                            if ($validate) {
                                $answer = Answers::updateOrCreate(['id' => $questionData['id'] ?? null], ['intentions_id' => $intention->id ?? null]);
                            }
                            foreach ($answerData['answer'] as $languageData) {
                                $validate = false;
                                if ($languageData['text'] !== null) {

                                    $existAnswer = Answers::with('intentions', 'answersLanguage')
                                    ->whereHas('intentions', function ($query) use ($request) {
                                        $query->where('chatbot_id', $request->chatbot_id);
                                    })
                                    ->whereHas('answersLanguage', function ($query) use ($languageData) {
                                        $query->where('answers', $languageData['text'])
                                              ->where('language', $languageData['name_language']);
                                    })
                                    ->where('intentions_id', $intention->id)
                                    ->exists();


                                    if (!$existAnswer) {
                                        AnswersLanguage::updateOrCreate(
                                            [
                                                'answers_id' => $answer->id,
                                                'answers' => $languageData['text'],
                                                'language' => $languageData['name_language']
                                            ]
                                        );
                                    } else {
                                        break;
                                    }
                                }
                            }
                            $existA = AnswersLanguage::where('answers_id', $answer->id)->exists();
                            if (!$existA) {
                                Answers::where('id', $answer->id)->delete();
                            }
                        }
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::info('error', ['error' => $e]);
                        return response()->json(['error' => $e->getMessage()], 500);
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
    public function exportIntentionsXlsx($data)
    {
        $data = json_decode(base64_decode($data));
        dd($data);
    }
}
