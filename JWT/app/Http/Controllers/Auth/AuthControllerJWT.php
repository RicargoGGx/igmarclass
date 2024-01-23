<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use jwt;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;



class AuthControllerJWT extends Controller
{

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required|max:60',
            'email' => 'required|email|max:100|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>bcrypt($request->password)
        ]);

        $token= jwt::fromUser($user);
        
        Log::channel('slack')->info('Error al crear el usuario: ' .$request->get('name'));

        return response()->json([
            'user'=>$user,
            'token'=>$token
        ], 201);

    }

    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
    
        try {
            if (! $token = Auth::guard('jwt')->attempt($credentials)) {
                Log::channel('slack')->alert('Intento de inicio de sesión no autorizado para el usuario con email: ' . $credentials['email']);
                return response()->json(['error' => 'No autorizado'], 401);
            } else {
                Log::channel('slack')->info('Inicio de sesión exitoso para el usuario con email: ' . $credentials['email']);
            }
        } catch (\JWTException $e) {
            Log::error('Error al generar el token: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    
        return $this->respondWithToken($token);
    }
    
    private function respondWithToken($token)
    {
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('jwt')->factory()->getTTL() * 60,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Log::channel('slack')->info('Cerró sesión el usuario: ' .$request->get('name'));
        return response()->json(['message'=>'Logged out'], 200);
    }
}
