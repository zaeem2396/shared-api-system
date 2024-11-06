<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
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

Route::group(['prefix' => 'author'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::get('get', [AuthController::class, 'get']);
    Route::put('update', [AuthController::class, 'update']);
    Route::put('updatePassword', [AuthController::class, 'updatePassword']);
    Route::get('verifyEmail', [AuthController::class, 'verifyEmail']);
    Route::post('resendVerificationLink', [AuthController::class, 'verificationLink']);
});

Route::group(['prefix' => 'blog'], function () {
    Route::post('create', [BlogController::class, 'createBlog']);

    // Blog category
    Route::group(['prefix' => 'category'], function () {
        Route::post('create', [BlogController::class, 'create']);
        Route::get('get', [BlogController::class, 'getCategory']);
        Route::put('update', [BlogController::class, 'update']);
    });
});
