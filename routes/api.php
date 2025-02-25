<?php

use App\Http\Controllers\AppSettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\VendorController;
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
/* Global routes */

Route::group(['prefix' => 'appSettings'], function () {
    Route::post('create', [AppSettingsController::class, 'createSettings']);
    Route::get('getAppSettings', [AppSettingsController::class, 'getAppSettings']);
});

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
Route::post('vendor/vendorStore', [VendorController::class, 'vendorStore']);
Route::get('vendor/vendorProfile', [VendorController::class, 'vendorProfile']);
Route::post('vendor/updateStore', [VendorController::class, 'updateStore']);

/* Vendora product api end points */
Route::group(['prefix' => 'product'], function () {
    Route::post('create', [ProductController::class, 'addProduct']);
    Route::get('get', [ProductController::class, 'getProduct']);
});

/* Vendora product category and sub-category api end point */
Route::post('vendor/category', [CategoryController::class, 'category']);
Route::post('vendor/subCategory', [SubCategoryController::class, 'subCategory']);
