<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');

Route::get('/main', function () {
    // Nota: Si tus vistas están en resources/views/menu/main.blade.php
    return view('menu.main'); // ¡Asegúrate de que la vista se llama correctamente!
})->middleware('auth');