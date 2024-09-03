<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Documentación Diputación Alicante",
 *      description="Apis Alicante",
 * )
 */
class RoleController extends AppBaseController
{
    public function __construct()
    {
    }
    /**
     *
     * Se muestra el listado de los registros solicitados.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getRoleData",
     *     tags={"roles"},
     *     summary="Mostrar el listado de roles",
     *     @OA\Response(
     *         response=200,
     *         description="Mostrar todos los roles."
     *     ),
     *     @OA\Response(
     *         response="default",
     *         description="Ha ocurrido un error."
     *     )
     * )
     */
    public function getRoleData()
    {
        try {
            $roles = Role::with('permissions')->get();
            return response()->json($roles);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los datos de roles'], 422);
        }
    }

    public function getRoles()
    {
        try {
            $roles = Role::get();
            return response()->json($roles);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los roles'], 422);
        }
    }

    /**
     * Obtiene todos los datos de permisos.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *     path="/api/getPermissionData",
     *     tags={"permissions"},
     *     summary="Obtener todos los datos de permisos.",
     *     description="Devuelve todos los datos de permisos disponibles en el sistema.",
     *     @OA\Response(
     *         response=200,
     *         description="Datos de permisos obtenidos correctamente.",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error al obtener los datos de permisos.",
     *         @OA\JsonContent(example={"message": "Error al obtener los datos de permisos"})
     *     )
     * )
     */
    public function getPermissionData()
    {
        try {
            $permissions = Permission::all();
            return response()->json($permissions);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener los datos de permisos'], 422);
        }
    }

    /**
     * Crea un nuevo rol con los permisos proporcionados.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/createRole",
     *     tags={"roles"},
     *     summary="Crear un nuevo rol con los permisos proporcionados.",
     *     description="Crea un nuevo rol en la base de datos y asigna los permisos especificados.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del rol a crear.",
     *         @OA\JsonContent(
     *             required={"name", "permissions"},
     *             @OA\Property(property="name", type="string", example="Rol Ejemplo"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer", example=1))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="El rol se creó exitosamente.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Rol creado exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El rol ya existe en la base de datos.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="El rol ya existe en la base de datos")
     *         )
     *     )
     * )
     */
    public function createRole(Request $request)
    {
        // Buscar si el rol ya existe en la base de datos
        $existingRole = Role::where('name', $request->name)->exists();

        if ($existingRole) {
            return response()->json(['message' => 'El rol ya existe en la base de datos'], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        $permissions = $request->permissions;
        // Asignar los permisos al rol
        foreach ($permissions as $permission) {
            $role->givePermissionTo($permission);
        }
        return response()->json([
            'message' => 'Rol creado exitosamente', 'success' => true, 'data'=> $role
        ], 201);
    }

    /**
     * Obtiene un rol con sus permisos.
     *
     * @param int $id El ID del rol.
     * @return \Illuminate\Http\Response
     *
     * @OA\Get(
     *     path="/api/getRoleId/{id}",
     *     tags={"roles"},
     *     summary="Obtiene un rol con sus permisos",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del rol",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol con sus permisos obtenidos correctamente."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error al obtener el rol."
     *     )
     * )
     */
    public function getRoleId($id)
    {
        try {
            // Obtener el rol con los permisos
            $roleWithPermissions = Role::where('roles.id', $id)->with('permissions')->get();
            return response()->json($roleWithPermissions);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al obtener el rol'], 422);
        }
    }

    /**
     * Actualiza un rol existente.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *     path="/api/updateRole",
     *     tags={"roles"},
     *     summary="Actualizar un rol existente.",
     *     description="Actualiza un rol existente en la base de datos.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos del rol a actualizar.",
     *         @OA\JsonContent(
     *             required={"id", "name", "permissions"},
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Nuevo Nombre de Rol"),
     *             @OA\Property(property="permissions", type="array", @OA\Items(type="integer", example=1))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rol actualizado exitosamente."
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="El rol ingresado ya existe o los datos proporcionados son inválidos.",
     *         @OA\JsonContent(example={"message": "El rol ingresado ya existe."})
     *     )
     * )
     */
    public function updateRole(Request $request)
    {
        try{
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'permissions' => 'required|array|min:1'
            ]);
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->messages();
            $errorMessage = '';

            foreach ($errors as $field => $messagesError) {
                $errorMessage .= "El campo '$field' es obligatorio. ";
            }
            return response()->json(['success' => false, 'error' => $errorMessage], 422);
        }

        foreach($request->permissions as $perm){
            $permissionExists = Permission::where('name', $perm)->exists();

            if(!$permissionExists){
                return response()->json(['success' => false, 'message' => 'El permiso '.$perm.' no existe. Puedes consultar los permisos existentes con getPermissionData']);
            }
        }

        $role = Role::find($request->id);

        if ($role && !Role::where('name', $request->name)->where('id', '!=', $role->id)->exists()) {
            $role->name = $request->name;
            $role->save();
            $permissions = $request->permissions;
            $role->syncPermissions($permissions);
            return response()->json([
                'message' => 'Rol actualizado exitosamente', 'success' => true
            ], 201);
        } else {
            return response()->json(['success'=> false,'message' => 'El rol ingresado ya existe.'], 422);
        }
    }
    /**
     * Elimina un rol existente.
     *
     * @param int $roleId ID del rol a eliminar.
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Delete(
     *     path="/api/deleteRole/{roleId}",
     *     tags={"roles"},
     *     summary="Eliminar un rol existente.",
     *     description="Elimina un rol existente en la base de datos.",
     *     @OA\Parameter(
     *         name="roleId",
     *         in="path",
     *         required=true,
     *         description="ID del rol a eliminar, en la URL.",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rol eliminado exitosamente.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rol eliminado exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Rol asociado a un usuario, no puede ser eliminado.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Este rol está asociado a algún usuario y no puede ser eliminado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="El rol no existe.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="El rol no existe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error al eliminar el rol.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Error al eliminar el rol")
     *         )
     *     )
     * )
     */
    public function deleteRole($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            if ($role->users()->exists()) {
                return response()->json(['message' => 'Este rol está asociado a algún usuario y no puede ser eliminado'], 400);
            }
            $role->permissions()->detach();
            $role->delete();
            return response()->json(['message' => 'Rol eliminado exitosamente']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'El rol no existe'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar el rol'], 500);
        }
    }
}
