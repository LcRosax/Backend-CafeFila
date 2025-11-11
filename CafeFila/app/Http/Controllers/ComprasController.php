<?php

namespace App\Http\Controllers;

use App\Models\Compras;
use Illuminate\Http\Request;

class ComprasController extends Controller
{
    public function listar(){
        try {
            $compras = Compras::with('usuario')->get();

            return response()->json($compras, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar as compras dos usuários.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
