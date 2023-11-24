<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReserveController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
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

Route::group(['prefix' => '/auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify', [AuthController::class, 'verify']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')
    ->post('/logout', [AuthController::class, 'logout']);

Route::group(['middleware' => ['auth:sanctum'], 'prefix' => '/user'], function () {
    Route::get('/profile', [UserController::class, 'show']);
    Route::post('/profile/picture', [UserController::class, 'avatar']);
    Route::post('/update', [UserController::class, 'update']);
    Route::post('/reserves', [UserController::class, 'reserves']);
    Route::post('/promote/admin', [UserController::class, 'promote']);

    Route::group(['middleware' => 'admin'], function () {
        Route::get('', [UserController::class, 'index']);
    });
});

Route::get('/tag', [TagController::class, 'index']);
Route::get('/photos/{tag_name?}', [TagController::class, 'photos']);
Route::group(['middleware' => 'auth:sanctum', 'prefix' => '/tag'], function (){
    Route::middleware(['admin'])->post('', [TagController::class, 'store']);
    Route::middleware(['admin'])->put('/{file_name}', [TagController::class, 'update']);
    Route::middleware(['admin'])->delete('/{tag_id}', [TagController::class, 'delete']);
    Route::middleware(['admin'])->delete('/photo/{file_name}', [TagController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->post('/service', [ServiceController::class, 'store']);
Route::middleware(['auth:sanctum'])->delete('/service/{id}', [ServiceController::class, 'destroy']);
Route::middleware(['auth:sanctum'])->put('/service/{id}', [ServiceController::class, 'update']);
Route::middleware(['auth:sanctum'])->get('/service', [ServiceController::class, 'index']);
Route::middleware(['auth:sanctum'])->get('/service/{id}', [ServiceController::class, 'show']);


Route::group(['middleware' => ['auth:sanctum'], 'prefix' => '/reserve'], function () {
    Route::get('/', [ReserveController::class, 'index']);
    Route::post('/', [ReserveController::class, 'create']);

    Route::delete('/{id}', [ReserveController::class, 'delete']);
});
