<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\AdminController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{id}', [ApartmentController::class, 'show']);

Route::get('/users/{userId}/status', [AdminController::class, 'checkStatus']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/check-status', [AuthController::class, 'checkStatus']);
    Route::post('/profile/toggle-2fa', [AuthController::class, 'toggleTwoFactor']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/update', [AuthController::class, 'updateProfile']);
    Route::post('/profile/change-password', [AuthController::class, 'changePassword']);

    Route::post('/apartments/{id}/favorite', [FavoriteController::class, 'toggle']);
    Route::get('/my-apartments', [ApartmentController::class, 'myApartments']);
    Route::post('/apartments', [ApartmentController::class, 'store']);
    Route::match(['put', 'post'], '/apartments/{id}', [ApartmentController::class, 'update']);
    Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);
    Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

    Route::get('/owner/my-properties', [OwnerController::class, 'myProperties']);
    Route::get('/owner/bookings', [OwnerController::class, 'getBookings']);
    Route::post('/owner/bookings/{id}/status', [OwnerController::class, 'updateBookingStatus']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    Route::post('/send_message', [ChatController::class, 'sendMessage']);
    Route::get('/get_messages', [ChatController::class, 'getMessages']);
    Route::get('/get_my_chats', [ChatController::class, 'getMyChats']);
});



Route::middleware(['auth:sanctum', RoleMiddleware::class . ':admin'])->prefix('admin')->group(function () {

    Route::get('/stats', [AdminController::class, 'getDashboardStats']);

    Route::get('/users/pending', [AdminController::class, 'pendingUsers']);
    Route::get('/all-users', [AdminController::class, 'getAllUsers']);
    Route::get('/users/{userId}', [AdminController::class, 'showUser']);
    Route::post('/users/{userId}/approve', [AdminController::class, 'approveUser']);
    Route::post('/users/{userId}/reject', [AdminController::class, 'rejectUser']);
    Route::post('/users/{userId}/ban', [AdminController::class, 'banUser']);
    Route::post('/users/{id}/activate', [AdminController::class, 'activateUser']);

    Route::get('/apartments/pending', [AdminController::class, 'pendingApartments']);
    Route::get('/all-apartments', [AdminController::class, 'getAllApartments']);
    Route::post('/apartments/{id}/approve', [AdminController::class, 'approveApartment']);
    Route::post('/apartments/{id}/reject', [AdminController::class, 'rejectApartment']);
    Route::delete('/apartments/{id}', [AdminController::class, 'forceDeleteApartment']);

    Route::get('/all-bookings', [AdminController::class, 'getAllBookings']);
});
