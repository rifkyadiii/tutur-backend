<?php

use App\Http\Controllers\{
   UserController,
   AlbumController,
   CardController,
   SearchController
};
use Illuminate\Support\Facades\Route;
// use Google\Client;

Route::post('/user/add', [UserController::class, 'store']);

Route::middleware(['firebase.auth'])->group(function () {
    // Users
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user', [UserController::class, 'show']);
    Route::put('/user/update', [UserController::class, 'update']);
    Route::post('/user/update-photo', [UserController::class, 'updatePhoto']);
    Route::put('/user/update-password', [UserController::class, 'updatePassword']);
    Route::delete('/user/delete', [UserController::class, 'destroy']);

    // Albums
    Route::post('/album/add', [AlbumController::class, 'store']);
    Route::get('/albums', [AlbumController::class, 'index']);
    Route::get('/album', [AlbumController::class, 'show']);
    Route::put('/album/update', [AlbumController::class, 'update']);
    Route::delete('/album/delete', [AlbumController::class, 'destroy']);
    // Route::get('/oauth2-token', function () {
    //     try {
    //         // Cek cache terlebih dahulu
    //         $cachedToken = Cache::get('gcs_token');
    //         if ($cachedToken) {
    //             return response()->json([
    //                 'access_token' => $cachedToken
    //             ]);
    //         }

    //         $client = new Client();
    //         $credentialsPath = storage_path('app/tutur-api-a3d132052fb3.json');

    //         if (!file_exists($credentialsPath)) {
    //             return response()->json([
    //                 'error' => 'Service account credentials not found'
    //             ], 500);
    //         }

    //         $client->setAuthConfig($credentialsPath);
    //         $client->addScope('https://www.googleapis.com/auth/devstorage.read_only');

    //         $token = $client->fetchAccessTokenWithAssertion();

    //         if (!isset($token['access_token'])) {
    //             return response()->json([
    //                 'error' => 'Failed to obtain access token'
    //             ], 500);
    //         }

    //         // Simpan token ke cache tanpa expiry (forever)
    //         Cache::forever('gcs_token', $token['access_token']);

    //         return response()->json([
    //             'access_token' => $token['access_token']
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'error' => 'Failed to generate token: ' . $e->getMessage()
    //         ], 500);
    //     }
    // })->middleware('throttle:60,1');

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

    // Sentence Template
    Route::post('/sentence-templates/store', [SentenceTemplateController::class, 'store']);
    Route::get('/sentence-templates/index', [SentenceTemplateController::class, 'index']);
    Route::post('/sentence-templates/show', [SentenceTemplateController::class, 'show'])->name('sentence-templates.show');
    Route::post('/sentence-templates/update', [SentenceTemplateController::class, 'update'])->name('sentence-templates.update');
    Route::post('/sentence-templates/destroy', [SentenceTemplateController::class, 'destroy'])->name('sentence-templates.destroy');
    Route::post('/sentence-templates/cards/add', [SentenceTemplateController::class, 'addCard'])->name('sentence-templates.cards.add');
    Route::get('/sentence-templates/cards', [SentenceTemplateController::class, 'getCards'])->name('sentence-templates.cards.get');
    Route::delete('/sentence-templates/cards/remove', [SentenceTemplateController::class, 'removeCard'])->name('sentence-templates.cards.remove');
 });
