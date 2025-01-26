<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VendorController;
use App\Models\BlogCategory;
use App\Models\Review;
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

/* Shared routes/common routes */

$sharedRoutes = function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify', [AuthController::class, 'verify']);
    Route::get('get', [AuthController::class, 'get']);
    Route::put('update', [AuthController::class, 'update']);
    Route::put('updatePassword', [AuthController::class, 'updatePassword']);
    Route::get('verifyEmail', [AuthController::class, 'verifyEmail']);
    Route::post('resendVerificationLink', [AuthController::class, 'verificationLink']);
    Route::get('fetch', [AuthController::class, 'authors']);
};

/* Blog api endpoints */
Route::group(['prefix' => 'author'], $sharedRoutes);

Route::group(['prefix' => 'blog'], function () {
    Route::post('create', [BlogController::class, 'createBlog']);
    Route::post('update', [BlogController::class, 'updateBlog']);
    Route::get('fetch', [BlogController::class, 'getBlog']);
    Route::get('fetch-blog-by-category', [BlogController::class, 'getTodaysBlogsByCategory']);

    // Blog category
    Route::group(['prefix' => 'category'], function () {
        Route::post('create', [BlogController::class, 'create']);
        Route::get('get', [BlogController::class, 'getCategory']);
        Route::put('update', [BlogController::class, 'update']);
    });

    // Blog review
    Route::group(['prefix' => 'review'], function () {
        Route::post('create', [ReviewController::class, 'createReview']);
        Route::get('get', [ReviewController::class, 'getReview']);
    });
});

/* Vendora api endpoints */
Route::group(['prefix' => 'vendor'], $sharedRoutes);
Route::post('vendor/updateVendorStore', [VendorController::class, 'updateVendorStore']);
