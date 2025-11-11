<?php

use App\Http\Controllers\FilaController;
use App\Http\Controllers\UsuarioController;
use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

Route::prefix('/usuarios')->group(function () {
    Route::get("", [UsuarioController::class, "listar"]);
    Route::get("/filtro", [UsuarioController::class, "buscarPorEmail"]);
    Route::get("/{id}", [UsuarioController::class, "buscarPorId"]);
    Route::post("", [UsuarioController::class, "criar"]);
    Route::post("/login", [UsuarioController::class, "login"]);
    Route::patch("/{id}", [UsuarioController::class, "atualizar"]);
});

Route::prefix("/fila")->group(function () {
    Route::get("", [FilaController::class, "listar"]);
    Route::get("/{pos}", [FilaController::class, "buscarPorPosicao"]);
    Route::post("/entrar", [FilaController::class, "entrarNaFila"]);
    Route::delete("/sair/{usuario_id}", [FilaController::class, "sairDaFila"]);
});