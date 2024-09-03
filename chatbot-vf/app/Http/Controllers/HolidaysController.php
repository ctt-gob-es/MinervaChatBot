<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use App\Models\HolidayLanguage;
use App\Models\Chatbot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class HolidaysController extends Controller
{
    /**
     * Obtiene los días festivos de un chatbot por su ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getChatbotHolidaysApi/{id}",
     *     tags={"chatbot_holidays"},
     *     summary="Obtiene los días festivos de un chatbot por su ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="from",
     *         in="query",
     *         description="Fecha de inicio del rango de búsqueda (YYYY-MM-DD)(opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="to",
     *         in="query",
     *         description="Fecha de fin del rango de búsqueda (YYYY-MM-DD)(opcional)",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             format="date"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Se devuelven los días festivos del chatbot"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron días festivos para el chatbot"
     *     )
     * )
     */
    public function getChatbotHolidays(Request $request, $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrio un error inesperado'], 404);

        try {
            $query = Holiday::where('chatbot_id', $id)->with('languages');

            if ($request->has('from') && $request->has('to')) {
                $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
                $query->whereBetween('day', [$request->from, $to]);
            }
            $holidays = $query->get();
            if ($holidays->isNotEmpty()) {
                return $holidays;
            } else {
                $holidays = [];
                return $holidays;
            }
        } catch (\Throwable $th) {
            Log::info('error', ['message' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Crea un nuevo día festivo para un chatbot por su ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/createChatbotHolidayApi/{id}",
     *     tags={"chatbot_holidays"},
     *     summary="Crea un nuevo día festivo para un chatbot por su ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"day", "name", "description", "languages"},
     *             @OA\Property(
     *                 property="day",
     *                 type="string",
     *                 format="date",
     *                 description="Fecha del día festivo (YYYY-MM-DD)"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Nombre del día festivo"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Descripción del día festivo"
     *             ),
     *             @OA\Property(
     *                 property="languages",
     *                 type="array",
     *                 description="Lista de mensajes en diferentes idiomas",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="language",
     *                         type="string",
     *                         description="nombre del idioma. EJ: 'castellano', 'ingles', 'valenciano'."
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         description="Mensaje en el idioma especificado"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Creación exitosa del día festivo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Creación de Festivo exitosa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo crear el día festivo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.")
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
    public function store(Request $request, $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);

        if (!Chatbot::where('id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'El id de chatbot no es valido'], 404);
        }

        $validator = Validator::make($request->all(), [
            'day' => 'required|date_format:Y-m-d',
            'name' => 'required|string',
            'description' => 'required|string',
            'languages' => 'nullable|array',
            'languages.*.language' => 'required_with:languages|string',
            'languages.*.message' => 'required_with:languages|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success'=>false, 'message'=>($validator->errors()->first())], 404);
        }

        try {
            $data = $request->all();
            $newHoliday = new Holiday();
            $newHoliday->day = $data['day'];
            $newHoliday->name = $data['name'];
            $newHoliday->description = $data['description'];
            $newHoliday->chatbot_id = $id;
            $newHoliday->save();

            foreach ($data['languages'] as $lang) {
                $newLanguage = new HolidayLanguage();
                $newLanguage->holiday_id = $newHoliday->id;
                $newLanguage->message = $lang['message'];
                $newLanguage->language = $lang['language'];
                $newLanguage->save();
            }

            return response()->json(['success' => true, 'message' => 'Creación de Festivo exitosa', 'data' => $newHoliday], 202);
        } catch (\Throwable $th) {

            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);

            return $errorMessage;
        }
    }

    /**
     * Actualiza la información de un día festivo por su ID.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id ID del día festivo
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/updateChatbotHolidayApi/{id}",
     *     tags={"chatbot_holidays"},
     *     summary="Actualiza la información de un día festivo por su ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del día festivo",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"day", "name", "description", "languages"},
     *             @OA\Property(
     *                 property="day",
     *                 type="string",
     *                 format="date",
     *                 description="Nueva fecha del día festivo (YYYY-MM-DD)"
     *             ),
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Nuevo nombre del día festivo"
     *             ),
     *             @OA\Property(
     *                 property="description",
     *                 type="string",
     *                 description="Nueva descripción del día festivo"
     *             ),
     *             @OA\Property(
     *                 property="languages",
     *                 type="array",
     *                 description="Lista de mensajes en diferentes idiomas",
     *                 @OA\Items(
     *                     @OA\Property(
     *                         property="language",
     *                         type="string",
     *                         description="Código del idioma"
     *                     ),
     *                     @OA\Property(
     *                         property="message",
     *                         type="string",
     *                         description="Mensaje en el idioma especificado"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Actualización exitosa del día festivo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Actualización de Festivo exitosa.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo actualizar el día festivo",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ocurrió un error inesperado.")
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
    public function update(Request $request, string $id)
    {
        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);

        if (!Holiday::where('id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'El id de festivo no es valido'], 404);
        }

        $validator = Validator::make($request->all(), [
            'day' => 'nullable|date_format:Y-m-d',
            'name' => 'nullable|string',
            'description' => 'nullable|string',
            'languages' => 'nullable|array',
            'languages.*.language' => 'required_with:languages|string',
            'languages.*.message' => 'required_with:languages|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success'=>false, 'message'=>($validator->errors()->first())], 404);
        }

        try {

            $data = $request->all();
            $holiday = Holiday::with('languages')->findOrFail($id);
            if (!$holiday) {
                return $errorMessage;
            }

            if (isset($data['day'])) {
                $holiday->day = $data['day'];
            }
            if (isset($data['name'])) {
                $holiday->name = $data['name'];
            }
            if (isset($data['description'])) {
                $holiday->description = $data['description'];
            }

            $holiday->save();


        if (!empty($data['languages'])) {
            foreach ($data['languages'] as $lang) {
                $existingLanguages = $holiday->languages()->where('language', $lang['language'])->first();
                if ($existingLanguages) {
                    $existingLanguages->message = $lang['message'];
                    $existingLanguages->save();
                } else {
                    $newLanguage = new HolidayLanguage();
                    $newLanguage->holiday_id = $holiday->id;
                    $newLanguage->language = $lang['language'];
                    $newLanguage->message = $lang['message'];
                    $newLanguage->save();
                }
            }
        }

            return response()->json(['success' => true, 'message' => 'Actualización de Festivo exitosa.'], 200);
        } catch (\Throwable $th) {
            Log::info('error', ['error' => $th->getMessage(), 'line' => $th->getLine()]);
            return $errorMessage;
        }
    }

    /**
     * Elimina un día festivo por su ID.
     *
     * @param string $id ID del día festivo a eliminar
     * @return \Illuminate\Http\Response
     *
     * @OA\Delete(
     *     path="/api/deleteChatbotHolidayApi/{id}",
     *     tags={"chatbot_holidays"},
     *     summary="Elimina un día festivo por su ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del día festivo a eliminar",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Día festivo eliminado correctamente"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo eliminar el día festivo"
     *     )
     * )
     */
    public function destroy(string $id)
    {


        $errorMessage = response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.'], 500);

        if (!Holiday::where('id', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'El id de festivo no es valido'], 404);
        }

        try {
            $holiday = Holiday::find($id);
            if (!$holiday) {
                return $errorMessage;
            }
            $holiday->delete();
            return response()->json(['success' => true, 'message' => 'Festivo eliminado correctamente.'], 200);
        } catch (\Throwable $th) {
            return $errorMessage;
        }
    }
}
