<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;





Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// مسار التحقق من حالة الحساب (Polling)
Route::get('users/{userId}/status', [AuthController::class, 'checkStatus']);



Route::middleware('auth:sanctum')->group(function () {


    Route::post('logout', [AuthController::class, 'logout']);


    Route::get('apartments', [ApartmentController::class, 'index']);
    Route::get('apartments/{id}', [ApartmentController::class, 'show']);

    Route::post('apartments/{id}/favorite', [FavoriteController::class, 'toggle']);

});




Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->prefix('admin')->group(function () {


    Route::get('users/pending', [AdminController::class, 'pendingUsers']);

    Route::get('users/{userId}', [AdminController::class, 'showUser']);

    Route::post('users/{userId}/approve', [AdminController::class, 'approveUser']);

    Route::delete('users/{userId}/reject', [AdminController::class, 'rejectUser']);
});
