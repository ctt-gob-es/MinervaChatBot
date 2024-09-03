<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\Intentions;
use Illuminate\Http\Request;
use App\Models\ResCombination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CombinationController extends Controller
{
    public function createCombinations($intencion, $chatbot)
    {
        if (!is_numeric($intencion)) {
            return response()->json(['error' => 'Invalid intention.'], 400);
        }

        DB::beginTransaction();

        try {

            $consulta = DB::select("SELECT createCombinations(?) as consulta", [$intencion])[0]->consulta;

            if ($intencion > 0 && $consulta) {
                $result = DB::select($consulta);

                if (!$result) {
                    return response()->json(['error' => 'Failed to execute query.'], 500);
                }

                ResCombination::where('intentions_id', $intencion)->delete();

                $combination = 0;

                foreach ($result as $row) {
                    $combination++;
                    $i = 0;
                    $data = [];

                    while ($i < count((array)$row) / 2) {
                        $i++;
                        $data[] = [
                            'combination_id' => $combination,
                            'concept_id' => $row->{'concepto' . $i},
                            'value' => $row->{'valor' . $i},
                            'intentions_id' => $intencion
                        ];
                    }

                    ResCombination::insert($data);
                }
            }
            $intentions = Intentions::where('chatbot_id', $chatbot)->get();
            foreach ($intentions as $intention) {
                Intentions::where('id', $intention->id)->update([
                    'training' => false
                ]);
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Combinacines creadas satisfactoriamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            Log::error('Error en la transacción: ' . $e->getMessage() . ' line ' . $e->getLine());

            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 404);

            throw new \ErrorException('Error al insertar combinaciones');
        }
    }

    public function getCombinations($intentionId)
    {
        if (!$intentionId) {
            return response()->json(['success' => false, 'message' => 'Identificador de la intención es requerido.'], 400);
        }

        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un problema al listar las combinaciones.'], 404);

        try {

            $combinations = ResCombination::select('combination_id', 'concept_id', 'value', 'response')->where('intentions_id', $intentionId)->get()->toArray();
            $groupedCombinations = $this->groupByProperty($combinations, 'combination_id');

            return response()->json(['success' => true, 'data' => $groupedCombinations], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    function groupByProperty($array, $property)
    {
        $result = [];
        $grouped = [];
        $headers = [];

        foreach ($array as $item) {
            $key = $item[$property];

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'items' => [],
                    'response' => 0,
                    'hasNonOneResponse' => false
                ];
            }

            $count = count($grouped[$key]['items']) + 1;

            $combinationKey = 'combination_id' . $count;
            $conceptKey = 'concept_id' . $count;
            $valueKey = 'value' . $count;

            $grouped[$key]['items'][] = [
                'combination' => $item['combination_id'],
                $conceptKey => $item['concept_id'],
                $valueKey => $item['value'],
            ];

            if ($item['response'] != 1) {
                $grouped[$key]['hasNonOneResponse'] = true;
            }

            if (!$grouped[$key]['hasNonOneResponse']) {
                $grouped[$key]['response'] = $item['response'];
            }

            if (!isset($headers[$count])) {
                $headers[$count] = ['title' => 'Value' . $count, 'key' => 'value' . $count];
            }
        }

        foreach ($grouped as $key => $group) {
            $groupArray = [
                'response' => $group['response']
            ];

            foreach ($group['items'] as $item) {
                $groupArray = array_merge($groupArray, $item);
            }

            $result[] = $groupArray;
        }

        return ['items' => array_values($result), 'headers' => array_values($headers)];
    }

    public function storeCombinations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'intention_id' => 'required',
            'items' => 'required|array',
            'items.*.combination' => 'required',
            'items.*.response' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $items = $request->input('items');
        $intentionId = $request->input('intention_id');

        DB::beginTransaction();

        try {
            foreach ($items as $key => $item) {
                $combination = ResCombination::where('intentions_id', $intentionId)
                    ->where('combination_id', $item['combination'])
                    ->first();

                if (!$combination) {
                    return response()->json(['error' => "Combination {$item['combination']} not found"], 404);
                }

                $combination->response = $item['response'];
                $combination->save();
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Combinacines guardadas satisfactoriamente.'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th->getTraceAsString());
            Log::error('Error: ' . $th->getMessage() . ' line ' . $th->getLine());

            return response()->json(['success' => false, 'message' => 'Error guardando combinaciones'], 500);
        }
    }
}
