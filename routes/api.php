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

    Route::group(['middleware' => 'api', 'prefix' => 'user'], function () {
        Route::post('/update', [UserController::class, 'updateInfo']);
        Route::get('/all', [UserController::class, 'getPeople']);
        Route::get('/{id}', [UserController::class, 'getUserInfo']);
        Route::post('/change-photo', [UserController::class, 'updateProfilePhoto']);
    });

    Route::group(['middleware' => 'api', 'prefix' => 'posts'], function () {
        Route::post('/add', [PostController::class, 'add']);
        Route::post('/edit/{id}', [PostController::class, 'edit']);
        Route::get('/', [PostController::class, 'getAll']);
        Route::get('/{id}', [PostController::class, 'show']);
        Route::get('/by-user/{id}', [PostController::class, 'getAllPostsByUser']);
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
    Route::group(['middleware' => 'api', 'prefix' => 'friends'], function () {
        Route::get('/', [UserController::class, 'getMyFriends']);
        Route::get('/by-user/{user_id}', [UserController::class, 'getUserFriends']);
        Route::get('/invitations', [UserController::class, 'getMyInvitations']);
        Route::get('/sent-invitations', [UserController::class, 'getSentInvitations']);
        Route::post('/add/{friend_id}', [UserController::class, 'sendFriendRequest']);
        Route::post('/{invitation_id}', [UserController::class, 'acceptFriendRequest']);
        Route::delete('/{invitation_id}', [UserController::class, 'cancelFriendRequest']);
    });
});
