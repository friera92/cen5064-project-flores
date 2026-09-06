<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('/api/user')->group(function () {
        Route::get('/profile', [UserController::class, 'show']);
        Route::patch('/profile', [UserController::class, 'update']);
        Route::delete('/', [UserController::class, 'delete']);
    });

    Route::prefix('/api/tool')->group(function () {
        Route::post('/add', []);
        Route::get('/show', []);
        Route::patch('/update', []);
        Route::delete('/', []);
    });
});
