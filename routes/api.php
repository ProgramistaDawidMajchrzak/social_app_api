<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LikesController;
use App\Http\Controllers\CommentsController;

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

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
});

Route::group(['middleware' => ['auth:api']], function () {

    Route::post('user/update', [UserController::class, 'updateInfo']);

    Route::group(['middleware' => 'api', 'prefix' => 'posts'], function () {
        Route::post('/add', [PostController::class, 'add']);
        Route::post('/edit/{id}', [PostController::class, 'edit']);
        Route::get('/', [PostController::class, 'getAll']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::delete('/{id}', [PostController::class, 'delete']);
    });

    Route::group(['middleware' => 'api', 'prefix' => 'likes'], function () {
        Route::post('/add/{post_id}', [LikesController::class, 'add']);
        Route::delete('/{post_id}', [LikesController::class, 'delete']);
    });

    Route::group(['middleware' => 'api', 'prefix' => 'comments'], function () {
        Route::get('/{post_id}', [CommentsController::class, 'getAll']);
        Route::post('/add/{post_id}', [CommentsController::class, 'add']);
        Route::delete('/{comment_id}', [CommentsController::class, 'delete']);
    });
});
