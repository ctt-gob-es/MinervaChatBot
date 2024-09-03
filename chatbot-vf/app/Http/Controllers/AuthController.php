<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function users(){
        $users = User::all();
        return response()->json($users);
    }

    /**
     * Inicia sesión un usuario.
     *
     * @OA\Post(
     *     path="/api/loginApi",
     *     tags={"login"},
     *     summary="Iniciar sesión",
     *     description="Permite a un usuario iniciar sesión y obtener un token de acceso.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login exitoso",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="abcdef123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Usuario no tiene rol API",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tu usuario no tiene el rol para hacer uso de la API")
     *         )
     *     )
     * )
     */

    public function login(Request $request){
        $rules=[
            'email' => 'required|email',
            'password' => 'required',
        ];
        $validator = \Validator::make($request->input(), $rules);
        if($validator->fails()){
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
            ],400);
        }

        $credentials = $request->only('email','password');
        if(!Auth::attempt($credentials)){
            return response()->json([
                'status' => false,
                'errors' => ['Unauthorized'],
            ],401);
        }
        $auth = Auth::user();
        $roles = $auth->getRoleNames();
        if($roles[0] !== 'Api'){
            return response()->json([
                'message' => 'Tu usuario no tiene el rol para hacer uso de la API'
            ], 500);
        } else {
            $user = User::where('email',$request->email)->first();

            $tokenDuration = $request->has('extended_token') ? now()->addDay() : now()->addMinutes(60);
            $token = $user->createToken('API', ['*'], $tokenDuration)->plainTextToken;
            $tokenParts = explode('|', $token);
            $plainToken = $tokenParts[1];

            return response()->json([
                'token' => $plainToken
            ], 200);
        }

    }

}
