<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\CityCouncils;
use Illuminate\Http\Request;
use App\Models\AccessHistory;
use App\Models\ManageClient;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    /**
     *
     * Se muestra el listado de los registros solicitados.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getUsersApi",
     *     tags={"users"},
     *     summary="Mostrar el listado de usuarios",
     *     @OA\Response(
     *         response=200,
     *         description="Mostrar todos los usuarios."
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error."
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = Auth::user(); // Obtener el usuario autenticado

        $query = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->select(
                'users.name',
                'users.email',
                'users.photo',
                'roles.name as rol',
                'users.id',
                'users.deleted_at',
                'users.created_at'
            )->withTrashed()->orderBy('users.created_at', 'DESC');

        if ($request->has('from') && $request->has('to')) {
            $to = date('Y-m-d', strtotime($request->to . ' +1 day'));
            $query->whereBetween('users.created_at', [$request->from, $to]);
        }

        $users = $query->get();

        $users = $users->map(function ($user) {
            return [
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo,
                'rol' => $user->rol,
                'id' => $user->id,
                'deleted_at' => $user->deleted_at,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json(['success' => true, 'data' => $users], 200);
    }

    /**
     * Almacena un nuevo usuario en la base de datos.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     *
     * @OA\Post(
     *     path="/api/saveUserApi",
     *     tags={"users"},
     *     summary="Almacenar un nuevo usuario",
     *     description="Este endpoint permite almacenar un nuevo usuario en la base de datos. El usuario debe proporcionar los datos requeridos, incluyendo una foto opcional y una lista de clientes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del usuario a almacenar",
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "role"},
     *             @OA\Property(property="name", type="string", description="Nombre del usuario", example="John Doe"),
     *             @OA\Property(property="email", type="string", description="Correo electrónico del usuario", example="john.doe@example.com"),
     *             @OA\Property(property="password", type="string", description="Contraseña del usuario", example="password123"),
     *             @OA\Property(property="role", type="integer", format="int64", description="ID del rol asignado al usuario", example=2),
     *             @OA\Property(property="photo", type="string", format="binary", description="Archivo de imagen del usuario"),
     *             @OA\Property(property="clients", type="string", format="json", description="Lista de clientes asignados al usuario", example="[{'id':1},{'id':2}]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario creado exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario creado exitosamente!"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El usuario con el correo electrónico proporcionado ya existe.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario con el email john.doe@example.com ya existe!"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ha ocurrido un error."),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $user = User::where('email', $request->email)->exists();
        if ($request->file('photo')) {
            $img = $request->file('photo');
            $fileImg = $img->getClientOriginalExtension();
        }
        if (!$user) {
            $user = new User();
            $role = Role::find($request->role);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();
            $user->assignRole($role->id);
            if ($request->file('photo')) {
                $name_img = date('Y_m_d_H_i_s') . '_user_' . $user->id . '.' . $fileImg;
                $img->move(public_path('support/userProfile'), $name_img);
                User::where('id', $user->id)->update([
                    'photo' => $name_img
                ]);
            }
            $clients = json_decode($request->clients, true);
            foreach ($clients as $client) {
                ManageClient::updateOrCreate(
                    [
                        'client_id' => $client['id'],
                        'user_id' => $user->id
                    ],
                );
            }
            return response()->json([
                'message' => 'Usuario creado exitosamente!', 'success' => true, 'data' => $user
            ], 201);
        } else {
            return response()->json([
                'message' => 'Usuario con el email ' . $request->email . ' ya existe!', 'success' => false
            ], 422);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}/edit",
     *     tags={"users"},
     *     summary="Obtener detalles de usuario para edición",
     *     description="Devuelve detalles de usuario, roles y clientes asociados.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario a editar",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalles del usuario, roles y clientes.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object"
     *                 ),
     *                 @OA\Property(
     *                     property="roles",
     *                     type="object"
     *                 ),
     *                 @OA\Property(
     *                     property="clients",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuario no encontrado."
     *     )
     * )
     */
    public function edit(string $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $council = CityCouncils::orderBy('created_at', 'DESC')->get();
            $userRole = $user->roles()->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'roles' => $userRole,
                    'clients' => $council
                ]
            ], 200);
        } catch (\Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.'
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/updateUserApi",
     *     tags={"users"},
     *     summary="Actualizar un usuario",
     *     description="Este endpoint permite actualizar un usuario existente en la base de datos. El usuario debe proporcionar los datos necesarios para la actualización, incluyendo una foto opcional y una lista de clientes.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "role"},
     *             @OA\Property(property="id", type="integer", description="ID del usuario", example=1),
     *             @OA\Property(property="name", type="string", description="Nombre del usuario", example="John Doe"),
     *             @OA\Property(property="email", type="string", description="Correo electrónico del usuario", example="john.doe@example.com"),
     *             @OA\Property(property="role", type="integer", format="int64", description="ID del rol asignado al usuario", example=2),
     *             @OA\Property(property="photo", type="string", format="binary", description="Archivo de imagen del usuario"),
     *             @OA\Property(property="clients", type="string", format="json", description="Lista de clientes asignados al usuario", example="[{'id':1},{'id':2}]"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuario actualizado exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario actualizado exitosamente!"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El usuario no existe.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El usuario no existe"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El correo electrónico ya está en uso por otro usuario.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario con el email john.doe@example.com ya existe!"),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function update(Request $request)
    {
        $user = User::withTrashed()->find($request->id);
        if (!$user) {
            return response()->json(['message' => 'El usuario no existe'], 404);
        }

        if ($request->has('email') && $request->email !== $user->email) {
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'message' => 'Usuario con el email ' . $request->email . ' ya existe!', 'success' => false
                ], 422);
            }
        }
        if ($request->has('role') && $request->role !== $user->roles()->pluck('id')->first()) {
            $role = Role::find($request->role);
            if ($role) {
                $user->syncRoles([$role->id]);
            }
        }
        if ($request->file('photo')) {
            if ($user->photo !== null) {
                unlink(public_path('support/userProfile/') .  $user->photo);
            }
            $img = $request->file('photo');
            $fileImg = $img->getClientOriginalExtension();
            $name_img = date('Y_m_d_H_i_s') . '_user_' . $request->id . '.' . $fileImg;
            $img->move(public_path('support/userProfile'), $name_img);
            $user->photo = $name_img;
        }
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->password !== null) {
            $user->password = bcrypt($request->password);
        }
        $user->save();
        $clients = json_decode($request->clients, true);
        ManageClient::where('user_id', $user->id)->delete();
        foreach ($clients as $client) {
            ManageClient::updateOrCreate(
                [
                    'client_id' => $client['id'],
                    'user_id' => $user->id
                ],
            );
        }
        if ($request->has('role')) {
            $role = Role::find($request->role);
            if ($role) {
                $user->syncRoles([$role->id]);
            }
        }
        return response()->json([
            'message' => 'Usuario actualizado exitosamente!', 'success' => true
        ], 201);
    }


    /**
     * @OA\Delete(
     *     path="/api/deleteUserApi/{id}",
     *     tags={"users"},
     *     summary="Eliminar un usuario",
     *     description="Elimina un usuario de forma permanente.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario a eliminar",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuario eliminado correctamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuario eliminado correctamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="No se puede eliminar este usuario porque tiene clientes asociados.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="No se puede eliminar este usuario porque tiene clientes asociados.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El usuario no existe.",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="El usuario no existe.")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        $user = User::withTrashed()->find($id);
        if (!$user) {
            return response()->json(['error' => 'El usuario no existe.'], 404);
        }
        $hasClients = ManageClient::where('user_id', $user->id)->exists();
        if ($hasClients) {
            return response()->json(['error' => 'No se puede eliminar este usuario porque tiene clientes asociados.'], 400);
        }
        if ($user->photo) {
            $imagePath = public_path('support/userProfile') . '/' . $user->photo;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        $user->roles()->detach();
        $user->forceDelete();
        return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
    }

    public function getUser($id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            return response()->json(['success' => true, 'data' => $user], 200);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'No se pudo encontrar el usuario.'], 404);
        }
    }

    public function updateUser(Request $request, $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            if ($request->file('photo')) {
                $img = $request->file('photo');
                $fileImg = $img->getClientOriginalExtension();
                $name_img = date('Y_m_d_H_i_s') . '_user_' . $user->id . '.' . $fileImg;
                $img->move(public_path('support/userProfile'), $name_img);
                $user->photo = $name_img;
            } else {
                $user->photo = null;
            }
            $user->save();
            return response()->json([
                'message' => 'Usuario actualizado exitosamente!', 'success' => true
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'No se pudo encontrar el usuario.'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}/newPassword",
     *     tags={"users"},
     *     summary="Establecer nueva contraseña",
     *     description="Establece una nueva contraseña para el usuario especificado.",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Nueva contraseña",
     *         @OA\JsonContent(
     *             required={"pass1"},
     *             @OA\Property(
     *                 property="pass1",
     *                 type="string",
     *                 format="password",
     *                 description="Nueva contraseña"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Contraseña guardada exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Contraseña guardada exitosamente!"),
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se pudo encontrar el usuario.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="No se pudo encontrar el usuario."),
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function newPassword(Request $request, $id)
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
            $user->password = bcrypt($request->input('pass1'));
            $user->save();

            return response()->json([
                'message' => 'Contraseña guardada exitosamente!', 'success' => true
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => 'No se pudo encontrar el usuario.'], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/updateState",
     *     tags={"users"},
     *     summary="Actualizar estado del usuario",
     *     description="Actualiza el estado de un usuario (activo/inactivo).",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos de la solicitud",
     *         @OA\JsonContent(
     *             required={"id", "state"},
     *             @OA\Property(property="id", type="integer", description="ID del usuario"),
     *             @OA\Property(property="state", type="string", description="Nuevo estado del usuario (activo/inactivo)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado actualizado exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Estado actualizado exitosamente a activo")
     *         )
     *     )
     * )
     */
    public function updateState(Request $request)
    {
        $user = User::withTrashed()->where('id', $request->id)->first();
        $state = '';
        if ($request->state == 'activo') {
            $state = 'inactivo';
            $user->delete();
        } else {
            $state = 'activo';
            $user->restore();
        }
        return response()->json(['message' => "Estado actualizado exitosamente a $state"]);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}/accessHistory",
     *     tags={"users"},
     *     summary="Historial de acceso del usuario",
     *     description="Obtiene el historial de acceso para un usuario específico.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del usuario",
     *         @OA\Schema(
     *             type="integer",
     *             format="int64"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historial de acceso obtenido exitosamente."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró historial de acceso para el usuario."
     *     )
     * )
     */
    public function getAccessHistory(Request $request, $id)
    {
        $accessHistory = AccessHistory::where('user_id', $request->id)->orderBy('created_at', 'DESC')->get();
        if ($accessHistory->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No se encontró historial de acceso para el usuario.'], 404);
        }
        return response()->json(['success' => true, 'data' => $accessHistory], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/getClientsApi",
     *     tags={"clients"},
     *     summary="Obtener clientes",
     *     description="Obtiene la lista de clientes.",
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clientes obtenida exitosamente.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron clientes."
     *     )
     * )
     */
    public function getClients()
    {
        $council = CityCouncils::orderBy('created_at', 'DESC')->get();
        if ($council->isEmpty()) {
            return response()->json(['message' => 'No se encontraron clientes.'], 404);
        }
        return response()->json($council);
    }

    /**
     * @OA\Get(
     *     path="/api/dataAdmin",
     *     tags={"users"},
     *     summary="Obtener datos de administrador",
     *     description="Obtiene los datos de los usuarios según el rol del administrador autenticado.",
     *     @OA\Response(
     *         response=200,
     *         description="Datos obtenidos exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="users",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="rol", type="string"),
     *                     @OA\Property(property="id", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="userRole", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado."
     *     )
     * )
     */
    public function getDataAdmin()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'No autorizado'], 401);
        }
        $user = Auth::user();
        if ($user->hasRole('SuperAdmin')) {
            $users = User::join('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->select(
                    'users.name',
                    'roles.name as rol',
                    'users.id'
                )
                ->whereNotIn('roles.name', ['Api'])
                ->orderBy('users.created_at', 'DESC')
                ->get();

            $userRole = 'SuperAdmin';
        } else {
            $users = User::where('id', $user->id)->get();
            $userRole = $user->roles->first()->name;
        }
        return ['users' => $users, 'userRole' => $userRole];
    }

    /**
     * Obtiene la información de chatbots asociados a un usuario.
     *
     * @param int $user_id El ID del usuario cuya información se desea obtener.
     * @return \Illuminate\Http\JsonResponse JSON con los datos del chatbot asociado al usuario.
     *
     * @OA\Get(
     *     path="/api/getClientUser/{user_id}",
     *     tags={"users"},
     *     summary="Obtener información del chatbot asociado a un usuario.",
     *     description="Devuelve la información del chatbot al que está asociado un usuario específico.",
     *     @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="ID del usuario.",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Información del chatbot asociado al usuario."
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontraron datos para el usuario dado."
     *     )
     * )
     */
    public function getClientUser($user_id)
    {
        $council = ManageClient::join('users', 'users.id', 'manage_clients.user_id')
            ->join('city_councils', 'city_councils.id', 'manage_clients.client_id')
            ->select('city_councils.*')
            ->where('manage_clients.user_id', $user_id)
            ->get();
        if ($council->isEmpty()) {
            return response()->json(['message' => 'No se encontraron datos para el usuario dado'], 404);
        }
        return response()->json($council);
    }
}
