<?php

use App\Http\Controllers\{
   UserController,
   AlbumController,
   CardController,
   SearchController
};
use Illuminate\Support\Facades\Route;

Route::post('/user/add', [UserController::class, 'store']);

Route::middleware(['firebase.auth'])->group(function () {
    // Users
    Route::put('/user/add-firebase-uid', [UserController::class, 'updateFirebaseUid']);
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);

    // Albums
    Route::post('/album/add', [AlbumController::class, 'store']);
    Route::get('/albums', [AlbumController::class, 'index']);
    Route::get('/album', [AlbumController::class, 'show']);
    Route::put('/album/update', [AlbumController::class, 'update']);
    Route::delete('/album/delete', [AlbumController::class, 'destroy']);

    // Cards
    Route::post('/card/add', [CardController::class, 'store']);
    Route::post('/card/attachToAlbum', [CardController::class, 'attachToAlbum']);
    Route::get('/cards', [CardController::class, 'getAllCards']);
    Route::get('/card', [CardController::class, 'show']);
    Route::get('/album/cards', [CardController::class, 'index']);
    Route::put('/card/update', [CardController::class, 'update']);
    Route::delete('/card/delete', [CardController::class, 'destroy']);

    // Search
    Route::get('/search', [SearchController::class, 'search']);
 });
