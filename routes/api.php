<?php

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ImageUploadController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\UserProfileController;
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

Route::get('/user', function (Request $request) {
    return $request::user();
})->middleware('auth:api');

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login']);
Route::post('firebase', [RegisterController::class, 'verifyFirToken']);
Route::put('profile/block', [UserProfileController::class, 'block']);

Route::group(['middleware' => ['auth:api']], function() {
    Route::resource('products', ProductController::class);
    Route::post('image-upload', [ImageUploadController::class, 'store']);
    Route::post('store-file', [DocumentController::class, 'store']);
    Route::get('profile', [UserProfileController::class, 'show']);
    Route::put('profile/update', [UserProfileController::class, 'update']);
    Route::post('profile/upload-avatar', [UserProfileController::class, 'upload']);
});