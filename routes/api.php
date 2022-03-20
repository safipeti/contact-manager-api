<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactsController;
use App\Http\Controllers\ForgotController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\PicturesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [AuthController::class, 'register']);
Route::post('forgot', [ForgotController::class, 'forgot']);
Route::post('reset', [ForgotController::class, 'reset']);

Route::middleware(['auth:api'])->group(function(){
    Route::get('hello', [HelloController::class, 'hello']);
    Route::apiResource('contacts', ContactsController::class);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('upload-picture', [PicturesController::class, 'upload']);
    Route::post('delete-picture', [PicturesController::class, 'deleteContactPicture']);
    Route::get('serve-picture', [PicturesController::class, 'serve']);
});


Route::get('user', [AuthController::class, 'user'])->middleware('auth:api');
