<?php

namespace App\Http\Controllers;

use App\Http\Requests\ComprasRequest;
use App\Models\Compras;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ComprasController extends Controller
{
    public function listar(Request $request)
    {
        try {
            $query = Compras::with(['usuario', 'alteradoPor'])
                           ->select('compra.*'); 

            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->filled('item')) {
                // Utiliza 'like' para buscar o item
                $query->where('item', 'like', '%' . $request->item . '%');
            }
            
            // O esboço menciona Filtro/Café, então podemos adicionar filtros booleanos se o DB suportar
            if ($request->filled('tipo') && in_array($request->tipo, ['cafe', 'filtro'])) {
                // Supondo que 'item' armazena o tipo de compra
                $query->where('item', $request->tipo);
            }

            if ($request->filled('data_inicio') && $request->filled('data_fim')) {
                $query->whereBetween('data_compra', [
                    $request->data_inicio . ' 00:00:00',
                    $request->data_fim . ' 23:59:59'
                ]);
            }

            $compras = $query->orderBy('data_compra', 'desc')->get();

            return response()->json($compras, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar as compras.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function comprar(ComprasRequest $request)
    {
        try{
            $validacao = $request->validated();
            
            $compra = Compras::create([
                'usuario_id' => $validacao["usuario_id"],
                'data_compra' => now('America/Sao_Paulo'),
                'item' => $validacao["item"],
                'quantidade' => $validacao["quantidade"],
            ]);
            
            return response()->json([
                "message" => "Compra efetuada com sucesso!",
                "compra" => $compra
            ], 201);

        } catch(\Exception $e){
            return response()->json([
                "message" => "Erro ao efetuar compra.",
                "error" => $e->getMessage()
            ], 500);
        }
    }
    
    public function atualizar(Request $request, $id)
    {
        try {
            $compra = Compras::findOrFail($id);

            $usuarioLogado = JWTAuth::parseToken()->authenticate();
        
            if (!$usuarioLogado || !$usuarioLogado->admin) {
                return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem editar compras.'
                ], 403);
            }
            
            $compra->item = $request->item ?? $compra->item;
            $compra->quantidade = $request->quantidade ?? $compra->quantidade;
            
            $compra->ultima_alteracao_por = $usuarioLogado->id;
            $compra->ultima_alteracao_em = now('America/Sao_Paulo');

            $compra->save();

            return response()->json([
                'message' => 'Compra atualizada com sucesso.',
                'data' => $compra
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar compra.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}