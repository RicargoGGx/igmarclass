<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class FileController extends Controller
{
    // JWT
    public function guardarArchivo(Request $request)
    {
        $jwtToken = $request->header('Authorization');

        // Validation rules
        $rules = [
            'image' => 'required|file|mimes:jpg,jpeg,png|max:2097152',
        ];

        // Custom error messages
        $messages = [
            'image.required' => 'The file field is required.',
            'image.file' => 'Invalid file format.',
            'image.mimes' => 'Invalid file extension. Allowed extensions: jpg, jpeg, png.',
            'image.max' => 'File size exceeds the maximum limit of 2 MB.',
        ];

        $validator = \Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        if (!$jwtToken) {
            Log::channel('slack')->alert('Missing Token');
            return response()->json(['error' => 'Unauthorized missing token'], 401);
        }

        if (Auth::guard('jwt')->check()) {
            $user = Auth::guard('jwt')->user();
            $imageFile = $request->file('image');
            if (!$imageFile) {
                Log::channel('slack')->info('El usuario: ' .$user->email. ' Intento subir un archivo vacio');
                return response()->json(['error' => 'Non-existent file'], 400);
            }
            $maxFileSize = 2097152;
            if ($imageFile->getSize() > $maxFileSize) {
                Log::channel('slack')->alert('El usuario: ' . $user->email . ' excedió el límite de peso de archivo: ' . $imageFile->getSize() . ' > ' . $maxFileSize);
                return response()->json(['error' => 'Límite excedido',], 400);
            }
            
            $allowedImageExtensions = ['jpg', 'jpeg', 'png','webp'];
            $fileExtension = strtolower($imageFile->getClientOriginalExtension());
            
            if (!in_array($fileExtension, $allowedImageExtensions)) {
                Log::channel('slack')->alert('El usuario: ' . $user->email . ' usó formato no válido: ' . $fileExtension);
                return response()->json(['error' => 'La extensión del archivo no es válida'], 400);
            }
            
            $path = Storage::disk('digitalocean')->putFile('ricardo', $imageFile, 'public');
            Log::channel('slack')->info('El usuario: ' . $user->email . ' Subió un archivo usando JWT');
            return response()->json(['message' => 'El archivo se subió exitosamente'], 200);
        } else {
            Log::channel('slack')->alert('No se autorizó la carga por token inválido.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function obtenerArchivos(Request $request)
    {   
        $jwtToken = $request->header('Authorization');

        if (!$jwtToken) {
            Log::channel('slack')->alert('Access Denied Missing Token');
            return response()->json(['error' => 'Missing Token'], 401);
        }
         
        if (Auth::guard('jwt')->check()) {
            $user = Auth::guard('jwt')->user();

            $archivos = Storage::disk('digitalocean')->files('ricardo');

            $nombresArchivos = array_map('basename', $archivos);

            Log::channel('slack')->info('El usuario: ' .$user->email. ' Solicitó los archivos');
            return response()->json(['archivos' => $nombresArchivos], 200);
        } else {
            Log::channel('slack')->alert('Recived an Unauthorized Token');
            return response()->json(['error' => 'Unauthorized Token'], 401);
        }
    }

    public function mostrarArchivoPorNombre($nombreArchivo)
    {
        if (Auth::guard('jwt')->check()) {
            $user = Auth::guard('jwt')->user();

            $rutaArchivo = 'ricardo/' . $nombreArchivo;

            if (Storage::disk('digitalocean')->exists($rutaArchivo)) {
                Log::channel('slack')->info('User: ' . $user->email . ' Requested: ' . $rutaArchivo);
                $contenidoArchivo = Storage::disk('digitalocean')->get($rutaArchivo);
                $mimeType = Storage::disk('digitalocean')->mimeType($rutaArchivo);
                return response($contenidoArchivo)->header('Content-Type', $mimeType);

            } else {
                Log::channel('slack')->alert('User: ' . $user->email . ' Requested: ' . $nombreArchivo);
                return response()->json(['error' => 'Non-existing file'], 404);
            }
        } else {
            Log::channel('slack')->alert('Recived Unauthorized Token');
            return response()->json(['error' => 'Unauthorized Token'], 401);
        }
    }
}