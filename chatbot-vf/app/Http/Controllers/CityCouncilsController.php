<?php

namespace App\Http\Controllers;

use App\Models\Chatbot;
use App\Models\CityCouncils;
use App\Models\ManageClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class CityCouncilsController extends Controller
{

    public function index(Request $request)
    {
        $query = ManageClient::join('users', 'users.id', 'manage_clients.user_id')
            ->join('city_councils', 'city_councils.id', 'manage_clients.client_id')
            ->join('users as u', 'u.id', '=', 'city_councils.creator_id')
            ->select(
                'city_councils.*',
                DB::raw('MAX(users.name) as admin'),
                DB::raw('MAX(u.name) as creator')
            )
            ->orderBy('city_councils.created_at', 'DESC')
            ->groupBy('city_councils.id', 'city_councils.name', 'city_councils.information', 'city_councils.creator_id', 'city_councils.created_at', 'city_councils.updated_at', 'city_councils.deleted_at');
        $user = Auth::user();
        $roles = $user->getRoleNames();
        if ($request->has('from') && $request->has('to')) {
            $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
            $query->whereBetween('city_councils.created_at', [$request->from, $to]);
        }

        if ($roles->contains('SuperAdmin')) {
            $cityCouncils = $query->whereNull('city_councils.deleted_at')->get();
        } else {
            $cityCouncils = $query->whereNull('city_councils.deleted_at')->where('manage_clients.user_id', auth()->id())->get();
        }
        $cityCouncils = $cityCouncils->map(function ($cityCouncil) {
            return [
                'id' => $cityCouncil->id,
                'name' => $cityCouncil->name,
                'information' => $cityCouncil->information ? $cityCouncil->information : 'N/A',
                'creator_name' => $cityCouncil->creator ? $cityCouncil->creator : 'N/A',
                'admin_name' => $cityCouncil->admin ? $cityCouncil->admin : 'N/A',
                'created_at' => $cityCouncil->created_at,
                'updated_at' => $cityCouncil->updated_at,
            ];
        });
        return ['data' => $cityCouncils, 'role' => $roles[0]];
    }

    /**
     * Almacena un nuevo cliente.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/saveClientApi",
     *     tags={"clients"},
     *     summary="Almacena un nuevo cliente",
     *     description="Almacena un nuevo cliente con la información proporcionada.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del cliente a almacenar",
     *         @OA\JsonContent(
     *             required={"initialState", "admin"},
     *             @OA\Property(property="initialState", type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre del cliente", example="Cliente XYZ"),
     *                 @OA\Property(property="information", type="string", description="Información adicional sobre el cliente", example="Información detallada sobre el cliente XYZ")
     *             ),
     *             @OA\Property(property="admin", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="ID del administrador del cliente", example=1)
     *                 )
     *             )
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente creado exitosamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", description="Indica si la operación fue exitosa", example=true),
     *             @OA\Property(property="message", type="string", description="Mensaje de éxito", example="Cliente creado exitosamente!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El nombre del cliente ya está en uso.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", description="Indica si la operación fue exitosa", example=false),
     *             @OA\Property(property="message", type="string", description="Mensaje de error", example="El nombre del cliente ya está en uso, digita otro.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error interno del servidor.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", description="Indica si la operación fue exitosa", example=false),
     *             @OA\Property(property="message", type="string", description="Mensaje de error", example="Hubo un error al procesar la solicitud.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        $existingClient = CityCouncils::where('name', $request->initialState['name'])->first();
        if ($existingClient) {
            return response()->json([
                'message' => 'El nombre del cliente ya está en uso, digita otro.', 'success' => false
            ], 422);
        } else {
            $creatorId = $request->has('creator_id') ? $request->creator_id : auth()->id();
            $cityCouncil = CityCouncils::updateOrCreate(
                [
                    'name' => $request->initialState['name']
                ],
                [
                    'information' => $request->initialState['information'],
                    'creator_id' => $creatorId
                ]
            );

            if (is_string($request->admin)) {
                $admin = json_decode($request->admin, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error al decodificar JSON', 'success' => false
                    ], 400);
                }
            } else {
                $admin = $request->admin;
            }

            foreach ($admin as $adm) {

                $manageClientExist = User::where('id', $adm['id'])->exists();
                if($manageClientExist){

                    ManageClient::updateOrCreate(
                        [
                            'client_id' => $cityCouncil->id,
                            'user_id' => $adm['id']
                        ],
                    );
                } else {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Error con el id '.$adm['id'].'. No se encontró ningun cliente con ese identificador.'
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Cliente creado exitosamente!', 'success' => true,
                'data' => $cityCouncil
            ], 201);
        }
    }


    /**
     * Edita un concejo de ciudad.
     *
     * @param string $id ID del concejo de ciudad a editar
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getClientId/{id}",
     *     tags={"clients"},
     *     summary="Obtiene detalles de un cliente específico",
     *     description="Obtiene los detalles de un cliente específico para su edición.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del cliente",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa. Devuelve los detalles del cliente y sus administradores.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", description="ID del cliente"),
     *                 @OA\Property(property="name", type="string", description="Nombre del cliente"),
     *                 @OA\Property(property="information", type="string", description="Información adicional sobre el cliente")
     *             ),
     *             @OA\Property(property="manage", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="ID del administrador"),
     *                     @OA\Property(property="name", type="string", description="Nombre del administrador"),
     *                     @OA\Property(property="rol", type="string", description="Rol del administrador")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El cliente no fue encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function edit(string $id)
    {
        $cityCouncil = CityCouncils::find($id);

        if (!$cityCouncil) {
            return response()->json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
        }

        $user = Auth::user();
        $roles = $user->getRoleNames();

        $manageClientQuery = ManageClient::join('users', 'users.id', 'manage_clients.user_id')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('city_councils', 'city_councils.id', 'manage_clients.client_id')
            ->select('users.id', 'users.name', 'roles.name as rol');

        if ($roles[0] !== 'SuperAdmin') {
            $manageClientQuery->where('manage_clients.user_id', auth()->id());
        }
        $manageClient = $manageClientQuery->where('manage_clients.client_id', $cityCouncil->id)->get();
        return ['data' => $cityCouncil, 'manage' => $manageClient];
    }

    /**
     * Actualiza un cliente.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $id ID único del cliente que se va a actualizar.
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Put(
     *     path="/api/updateClientsApi/{id}",
     *     tags={"clients"},
     *     summary="Actualiza un cliente existente",
     *     description="Actualiza un cliente existente y sus administradores.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID único del cliente que se va a actualizar.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del cliente a actualizar",
     *         @OA\JsonContent(
     *             required={"initialState", "admin"},
     *             type="object",
     *             @OA\Property(property="initialState", type="object",
     *                 @OA\Property(property="name", type="string", description="Nombre del cliente"),
     *                 @OA\Property(property="information", type="string", description="Información adicional sobre el cliente")
     *             ),
     *             @OA\Property(property="admin", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", description="ID del administrador del cliente")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente actualizado exitosamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", default="true", description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", default="Cliente actualizado exitosamente!", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El nombre del cliente ya está en uso.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", default="false", description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", default="El nombre del cliente ya está en uso, digita otro.", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function update(Request $request, string $id)
    {
        $existingClient = CityCouncils::where('name', $request->initialState['name'])
            ->where('id', '!=', $request->id)
            ->exists();
        if ($existingClient) {
            return response()->json([
                'message' => 'El nombre del cliente ya está en uso, digita otro.', 'success' => false
            ], 422);
        } else {
            CityCouncils::where('id', $id)->update([
                'name' => $request->initialState['name'],
                'information' => $request->initialState['information']
            ]);
            $admin = json_decode($request->admin, true);

            ManageClient::where('client_id', $id)->delete();

            foreach ($admin as $adm) {
                ManageClient::updateOrCreate(
                    [
                        'client_id' => $id,
                        'user_id' => $adm['id']
                    ],
                );
            }
            return response()->json([
                'message' => 'Cliente actualizado exitosamente!', 'success' => true
            ], 201);
        }
    }

    /**
     * Elimina un cliente.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Delete(
     *     path="/api/deleteClientApi/{id}",
     *     tags={"clients"},
     *     summary="Elimina un cliente",
     *     description="Elimina un cliente existente si no tiene administradores asociados ni chatbots creados.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del cliente que se va a eliminar, en la URL.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente eliminado correctamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", default="true", description="Indica si la operación fue exitosa"),
     *             @OA\Property(property="message", type="string", default="Cliente eliminado correctamente.", description="Mensaje de éxito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No se puede eliminar el cliente debido a administradores asociados.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="boolean", default="true", description="Indica si hubo un error"),
     *             @OA\Property(property="message", type="string", default="No se puede eliminar el cliente, hay administradores asociados.", description="Mensaje de error")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No se puede eliminar el cliente debido a chatbots creados.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="boolean", default="true", description="Indica si hubo un error"),
     *             @OA\Property(property="message", type="string", default="No se puede eliminar el cliente, hay chatbots creados.", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try{


            $cityCouncilExists = CityCouncils::where('id', $id)->exists();
            if($cityCouncilExists){
                $chatbotExists = Chatbot::where('city_councils_id', $id)->exists();

                if ($chatbotExists) {
                    return response()->json(['success' => false, 'message' => 'No se puede eliminar el cliente, hay chatbots creados.'], 401);
                }

                $cityCouncil = CityCouncils::where('id', $id)->first();
                $cityCouncil->delete();

                return response()->json(['success' => true, 'message' => 'Cliente eliminado correctamente.'], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'El id de cliente no existe.'], 400);
            }
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'Ocurrió un error inesperado.']);

        }
    }


    public function getAllCity()
    {
        $cities = CityCouncils::has('settings', '=', 0)->get();
        return response()->json(['success' => true, 'data' => $cities], 200);
    }

    /**
     * Obtiene los administradores de un cliente específico.
     *
     * @param int $id ID del cliente
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getAdminClientApi/{id}",
     *     tags={"clients"},
     *     summary="Obtener administradores de un cliente",
     *     description="Obtiene una lista de los administradores asociados a un cliente específico.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del cliente, en la URL",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa. Devuelve una lista de administradores del cliente.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="ID del administrador"),
     *                 @OA\Property(property="name", type="string", description="Nombre del administrador"),
     *                 @OA\Property(property="rol", type="string", description="Rol del administrador")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El cliente no fue encontrado.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", description="Mensaje de error")
     *         )
     *     )
     * )
     */
    public function getAdminClient($id)
    {
        $client = CityCouncils::find($id);
        if (!$client) {
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }
        $manageClientQuery = ManageClient::join('users', 'users.id', 'manage_clients.user_id')
            ->join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('city_councils', 'city_councils.id', 'manage_clients.client_id')
            ->select('users.id', 'users.name', 'roles.name as rol')
            ->where('manage_clients.client_id', $id)
            ->get();
        return response()->json($manageClientQuery);
    }
}
