<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas con autenticación
Route::middleware('auth:sanctum')->group(function () {
    
    Route::apiResource('users', UserController::class);
    
    Route::apiResource('transactions', TransactionController::class)->except(['destroy']);
    
    Route::get('transactions/user/{userId}', [TransactionController::class, 'getUserTransactions']);

    Route::get('transactions/export/csv', [TransactionController::class, 'exportCSV']);

    Route::get('transactions/stats/{userId}', [TransactionController::class, 'getUserStatistics']);
});