<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthControllerJWT;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\Auth\AuthControllerS;
use App\Http\Controllers\FileController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('users', [UserController::class, 'index']);

*/
#con jwt
Route::prefix('v1')->group(function () {
    Route::post('register', [AuthControllerJWT::class, 'register']);
    Route::post('loginjwt', [AuthControllerJWT::class, 'login']);
    Route::middleware('jwt.verify')->group(function () {
        Route::get('users', [UserController::class, 'index']);
    });
});


#con sanctum
Route::get('games', [GameController::class, 'index']);
Route::post('registerS', [AuthControllerS::class, 'register']);
Route::post('loginS', [AuthControllerS::class, 'login']);
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('logoutS', [AuthControllerS::class, 'logout']);
});

Route::prefix('v1')->group(function () {
    Route::post('save-file', [FileController::class, 'guardarArchivo']);
    Route::get('get-files', [FileController::class, 'obtenerArchivos']);
    Route::get('show-file/{nombreArchivo}', [FileController::class, 'mostrarArchivoPorNombre']);
});
