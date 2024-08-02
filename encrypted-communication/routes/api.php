<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(['prefix' => '/v1'], function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});


Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::post('/search/{query}', SearchController::class);

    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::post('/send-message/{recipient}', [ChatController::class, 'sendMessage']);
    Route::get('/messages/{recipient}', [ChatController::class, 'getMessages']);

});
