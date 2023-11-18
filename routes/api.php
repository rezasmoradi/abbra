<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReserveController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['prefix' => '/auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify', [AuthController::class, 'verify']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')
    ->post('/logout', [AuthController::class, 'logout']);

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => '/user'], function () {
    Route::get('/me', [UserController::class, 'show']);
    Route::post('/update', [UserController::class, 'update']);
    Route::post('/reserves', [UserController::class, 'reserves']);
    Route::post('/promote/admin', [UserController::class, 'promote']);

    Route::group(['middleware' => 'admin'], function () {
        Route::get('', [UserController::class, 'index']);
    });
});

Route::middleware('auth:sanctum')->prefix('/service')->apiResource('', ServiceController::class);

Route::middleware(['auth:sanctum'])->post('/service', [ServiceController::class, 'store']);
Route::middleware(['auth:sanctum'])->delete('/service/{id}', [ServiceController::class, 'destroy']);
Route::middleware(['auth:sanctum'])->put('/service/{id}', [ServiceController::class, 'update']);


Route::group(['middleware' => ['auth:sanctum'], 'prefix' => '/reserve'], function () {
    Route::get('/', [ReserveController::class, 'index']);
    Route::post('/', [ReserveController::class, 'create']);

    Route::delete('/{id}', [ReserveController::class, 'delete']);
});
