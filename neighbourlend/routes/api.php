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

    Route::prefix('/user')->group(function () {
        Route::get('/profile', [UserController::class, 'show']);
        Route::patch('/profile', [UserController::class, 'update']);
        Route::delete('/inactivate', [UserController::class, 'delete']);
    });

    Route::prefix('/tool')->group(function () {
        Route::post('/add', []);
        Route::get('/show', []);
        Route::patch('/update', []);
        Route::delete('/', []);
    });

    Route::prefix('/review')->group(function () {
        Route::post('/add', []);
        Route::get('/show', []);
    });
});

// Route::middleware(['auth:sanctum', 'can:admin-access'])->prefix('admin')->group(function () {
//     Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivate']);
//     Route::apiResource('/categories', CategoryController::class);
//     Route::post('/reservations/{reservation}/resolve-dispute', [AdminDisputeController::class, 'resolve']);
// });
