<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViettapController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/viettap/init', [ViettapController::class, 'init']);

Route::post('/viettap/submit', [ViettapController::class, 'submit']);

Route::get('/viettap/status', [ViettapController::class, 'status']);

Route::get('/viettap/list', [ViettapController::class, 'list']);

Route::get('/viettap/help', [ViettapController::class, 'help']);
