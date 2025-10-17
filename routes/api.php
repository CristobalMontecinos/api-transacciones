<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    
    // Rutas de usuarios
    Route::apiResource('users', UserController::class);
    
    // Rutas de transacciones
    Route::apiResource('transactions', TransactionController::class)->except(['destroy']);
    
    // Ruta adicional para ver transacciones de un usuario específico
    Route::get('transactions/user/{userId}', [TransactionController::class, 'userTransactions']);
});