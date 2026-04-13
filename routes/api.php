<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\QueryController;
use App\Http\Controllers\TelegramAuthController;
use App\Http\Controllers\TelegramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/tg/send-code', [TelegramAuthController::class, 'sendCode']);
Route::post('/tg/verify-code', [TelegramAuthController::class, 'verifyCode']);


Route::get('/tg/check-group', [TelegramController::class, 'check']);
Route::get('/tg/groups', [TelegramController::class, 'groups']);
Route::get('/tg/count', [TelegramController::class, 'count']);


Route::get('/query', [QueryController::class, 'index']);
Route::get('/query/{id}', [QueryController::class, 'show']);


Route::post('/group', [GroupController::class, 'store']);
