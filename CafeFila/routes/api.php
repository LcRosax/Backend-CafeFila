<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::prefix('/usuarios')->group(function () {
    Route::get("", [UsuarioController::class, "listar"]);
});