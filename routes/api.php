<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'auth.apikey', 'prefix' => 'V1'], function() {
    Route::post('/user/add', [UserController::class, 'createUser']);
    Route::get('/users/{limit?}', [UserController::class, 'getUsers']);
    Route::get('/user/{id}', [UserController::class, 'getUser']);
    Route::get('/users/load/stats', [UserController::class, 'getUserStats']);
    Route::get('/user/avatar/{id}', [UserController::class, 'getUserAvatar']);
});

Route::get('/version', [UserController::class, 'index']);

