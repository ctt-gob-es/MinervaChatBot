<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Day;
use App\Models\TimeSlot;
use App\Models\ScheduleDayTimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Obtiene el horario de un chatbot por su ID.
     *
     * @param int $id ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getBotScheduleApi/{id}",
     *     tags={"chatbot_schedules"},
     *     summary="Obtiene el horario de un chatbot por su ID",
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
     *         description="Se devuelve el horario del chatbot"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró el horario del chatbot"
     *     )
     * )
     */
    public function getSchedule($id)
    {

        $botNormalSchedules = ScheduleDayTimeSlot::where('id_chatbot', $id)
            ->whereHas('schedule', function ($query) {
                $query->where('type', 'normal');
            })
            ->with('schedule', 'dayTimeSlot.day', 'dayTimeSlot.timeSlot')
            ->get();

        $botSpecialSchedules = ScheduleDayTimeSlot::where('id_chatbot', $id)
            ->whereHas('schedule', function ($query) {
                $query->where('type', 'special');
            })
            ->with('schedule', 'dayTimeSlot.day', 'dayTimeSlot.timeSlot')
            ->get();

        $days = Day::all();
        $timeSlots = TimeSlot::all();

        return ['normalSchedule' => $botNormalSchedules, 'specialSchedule' => $botSpecialSchedules, 'days' => $days, 'time_slots' => $timeSlots];
    }

    /**
     * Actualiza la configuración de horario de un chatbot.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID del chatbot
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/updateBotScheduleApi/{id}",
     *     tags={"chatbot_schedules"},
     *     summary="Actualiza la configuración de horario de un chatbot",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del chatbot",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de configuración de horario a actualizar",
     *         @OA\JsonContent(
     *             required={"normal", "special", "activeSpecial"},
     *             @OA\Property(property="normal", type="array", @OA\Items(
     *                 @OA\Property(property="active", type="boolean", description="Estado activo del horario normal"),
     *                 @OA\Property(property="timeSlots", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", description="ID del intervalo de tiempo"),
     *                     @OA\Property(property="startTime", type="string", description="Hora de inicio del intervalo de tiempo"),
     *                     @OA\Property(property="endTime", type="string", description="Hora de fin del intervalo de tiempo")
     *                 ), description="Lista de intervalos de tiempo")
     *             ), description="Horario normal del chatbot"),
     *             @OA\Property(property="special", type="array", @OA\Items(
     *                 @OA\Property(property="active", type="boolean", description="Estado activo del horario especial"),
     *                 @OA\Property(property="timeSlots", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", description="ID del intervalo de tiempo"),
     *                     @OA\Property(property="startTime", type="string", description="Hora de inicio del intervalo de tiempo"),
     *                     @OA\Property(property="endTime", type="string", description="Hora de fin del intervalo de tiempo")
     *                 ), description="Lista de intervalos de tiempo")
     *             ), description="Horario especial del chatbot"),
     *             @OA\Property(property="activeSpecial", type="boolean", description="Indicador de activación del horario especial")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Configuración de horario actualizada correctamente"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="No se pudo actualizar la configuración de horario del chatbot"
     *     )
     * )
     */
    public function updateSchedule(Request $request, $id)
    {
        $data = $request->all();
        $normalSchedules = $data['normal'];
        $specialSchedules = $data['special'];
        $activeSpecial = $data['activeSpecial'];
        DB::beginTransaction();
        try {
            foreach ($normalSchedules as $normal) {
                foreach ($normal['timeSlots'] as $slot) {
                    $scheduleFound = ScheduleDayTimeSlot::where('id', $slot['id'])
                        ->with('schedule', 'dayTimeSlot.day', 'dayTimeSlot.timeSlot')
                        ->first();
                    $active = 0;
                    if ($activeSpecial) {
                        $active = 0;
                    } else {
                        if ($normal['active']) {
                            $active = 1;
                        } else {
                            $active = 0;
                        }
                    }
                    $scheduleModel = Schedule::where('type', 'normal')->where('active', $active)->first();
                    $scheduleFound->dayTimeSlot->start_time = $slot['startTime'];
                    $scheduleFound->dayTimeSlot->end_time = $slot['endTime'];
                    $scheduleFound->id_schedule = $scheduleModel->id;
                    $scheduleFound->save();
                    $scheduleFound->dayTimeSlot->save();
                }
            }

            foreach ($specialSchedules as $special) {

                foreach ($special['timeSlots'] as $slot) {
                    $specialScheduleFound = ScheduleDayTimeSlot::where('id', $slot['id'])
                        ->with('schedule', 'dayTimeSlot.day', 'dayTimeSlot.timeSlot')
                        ->first();
                    $active = 0;

                    if ($activeSpecial) {
                        if ($special['active']) {
                            $active = 1;
                        } else {
                            $active = 0;
                        }
                    } else {
                        $active = 0;
                    }
                    $scheduleModel = Schedule::where('type', 'special')->where('active', $active)->first();
                    $specialScheduleFound->dayTimeSlot->start_time = $slot['startTime'];
                    $specialScheduleFound->dayTimeSlot->end_time = $slot['endTime'];
                    $specialScheduleFound->id_schedule = $scheduleModel->id;
                    $specialScheduleFound->save();
                    $specialScheduleFound->dayTimeSlot->save();
                }
            }
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Configuración de horario actualizada correctamente.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar su configuración de horario '], 500);
        }
    }
}
