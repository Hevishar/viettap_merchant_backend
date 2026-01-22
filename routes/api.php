<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ViettapController;
use App\Http\Controllers\ProductController;

Route::post('/viettap/init', [ViettapController::class, 'init']);

Route::post('/viettap/submit', [ViettapController::class, 'submit']);

Route::get('/viettap/status', [ViettapController::class, 'status']);

Route::get('/viettap/list', [ViettapController::class, 'list']);

Route::get('/viettap/help', [ViettapController::class, 'help']);

Route::get('/products', [ProductController::class, 'index']);
Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::put('/products/{id}', [ProductController::class, 'update']);
Route::delete('/products/{id}', [ProductController::class, 'destroy']);

