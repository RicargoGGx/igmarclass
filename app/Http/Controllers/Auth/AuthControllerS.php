<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use \stdClass;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;



class AuthControllerS extends Controller
{
    //
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'=>'required|string|max:60',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|min:6|string'
        ]);
    
        if ($validator->fails()) {
            Log::channel('slack')->info('Error al crear el usuario: ' .$request->get('name'));
            return response()->json($validator->errors(), 400);
        }

        $user = User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);


        $token= $user->createToken('auth_token')->plainTextToken;

        /*
        $response = [
            'user'=>$user,
            'token'=>$token
        ];
        */
        Log::channel('slack')->info('Se creó el usuario: ' .$user);

        return response()->json(['data' => $user, 'access_token' => $token,'token_type' => 'Bearer'], 201);
    }

    public function login(Request $request)
    {
        if(!Auth::attempt($request->only('email','password'))){
            Log::channel('slack')->info('Error al inciar sesión de un usuario');

            return response()->json(['message'=>'Unauthorized'], 401);
        }

        $user = User::where('email',$request['email'])->firstOrFail();
        $token= $user->createToken('auth_token')->plainTextToken;

        Log::channel('slack')->info('Inicio sesión el usuario: ' .$user);

        return response()->json(['data' => $user, 'access_token' => $token,'token_type' => 'Bearer','user'=>$user], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Log::channel('slack')->info('Cerró sesión el usuario: ' .$request->get('name'));
        return response()->json(['message'=>'Logged out'], 200);
    }
}
