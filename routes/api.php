<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffScheduleController;
use App\Http\Controllers\UserController;

// Rutas públicas (solo identificar tenant, sin autenticación)
Route::middleware(['identify.tenant'])->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);
});

// Rutas autenticadas (identificar tenant + validar token)
Route::middleware(['identify.tenant', 'auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::get('/users/staff',  [UserController::class, 'staffByTenant']);

    // Cualquier usuario autenticado — clientes y staff
    Route::get('services',                 [ServiceController::class, 'index']);
    Route::get('services/{service}',       [ServiceController::class, 'show']);
    Route::get('staff-schedules',          [StaffScheduleController::class, 'index']);
    Route::get('bookings/available-slots', [BookingController::class, 'availableSlots']);

    // Clientes — crear y ver sus propias reservas
    Route::post('bookings',          [BookingController::class, 'store']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);

    // Owner y staff — gestión completa
    Route::middleware(['role:owner,staff'])->group(function () {
        Route::get('bookings',                           [BookingController::class, 'index']);
        Route::put('bookings/{booking}',                 [BookingController::class, 'update']);
        Route::get('dashboard/stats',                    [BookingController::class, 'stats']);
        Route::post('services',                          [ServiceController::class, 'store']);
        Route::put('services/{service}',                 [ServiceController::class, 'update']);
        Route::delete('services/{service}',              [ServiceController::class, 'destroy']);
        Route::post('staff-schedules',                   [StaffScheduleController::class, 'store']);
        Route::put('staff-schedules/{staffSchedule}',    [StaffScheduleController::class, 'update']);
        Route::delete('staff-schedules/{staffSchedule}', [StaffScheduleController::class, 'destroy']);
    });
});
