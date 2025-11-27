<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FilaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ComprasController;


Route::prefix('/usuarios')->group(function () {
    
    Route::post('', [UsuarioController::class, 'criar']);

    
    Route::post('/login', [UsuarioController::class, 'login']);
});



Route::middleware('jwt')->group(function () {

   
    Route::prefix('/usuarios')->group(function () {
        
        Route::get('/me', [UsuarioController::class, 'me']); 

      
        Route::get('', [UsuarioController::class, 'listar']);

     
        Route::get('/filtro', [UsuarioController::class, 'buscarPorEmail']);

      
        Route::get('/{id}', [UsuarioController::class, 'buscarPorId']);

    
        Route::patch('/{id}', [UsuarioController::class, 'atualizar']);

       
        Route::delete('/{id}', [UsuarioController::class, 'deletar']); 
    });



    Route::prefix('/fila')->group(function () {
        Route::get('', [FilaController::class, 'listar']);
        Route::get('/{pos}', [FilaController::class, 'buscarPorPosicao']);
        Route::post('/entrar', [FilaController::class, 'entrarNaFila']);
        Route::delete('/sair/{usuario_id}', [FilaController::class, 'sairDaFila']);
        Route::post('/concluir/{usuario_id}', [FilaController::class, 'concluirEVoltarParaFinal']);

       
        Route::patch('/adicionar_pedido/{item_type}', [FilaController::class, 'adicionarPedido']);
        Route::patch('/mover_proximo/{usuario_id}', [FilaController::class, 'moverParaProximo']);
    });


 
    Route::prefix('/compras')->group(function () {
        Route::get('', [ComprasController::class, 'listar']);
        Route::post('', [ComprasController::class, 'comprar']);
        Route::patch('/{id}', [ComprasController::class, 'atualizar']);
        
    
        Route::delete('/{id}', [ComprasController::class, 'deletar']); 
    });
});