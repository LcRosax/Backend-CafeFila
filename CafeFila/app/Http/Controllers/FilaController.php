<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilaRequest;
use App\Models\Fila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilaController extends Controller
{
    public function listar()
    {
       try {
            $fila = Fila::with('usuario')
                ->orderBy('posicao', 'asc')
                ->get();

            return response()->json($fila, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar a fila.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   
    public function buscarPorPosicao(string $pos)
    {
        try {
            $fila = Fila::where('posicao', $pos)
                ->with('usuario')
                ->first();

            if (!$fila) {
                return response()->json([
                    'message' => 'Nenhum registro encontrado para a posição informada.'
                ], 404);
            }

            return response()->json($fila, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar a posição na fila.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function entrarNaFila(FilaRequest $request){
       try {
            $request->all();

           $jaEstaNaFila = Fila::where('usuario_id', $request->usuario_id)->exists();

        if ($jaEstaNaFila) {
            return response()->json([
                'message' => 'Usuário já está na fila.'
            ], 400);
        }
            $ultimaPosicao = Fila::max('posicao') ?? 0;
            $novaPosicao = $ultimaPosicao + 1;

            $fila = Fila::create([
                'usuario_id' => $request->usuario_id,
                'posicao' => $novaPosicao,
            ]);

            return response()->json([
                'message' => 'Usuário entrou na fila com sucesso!',
                'dados' => $fila,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao entrar na fila.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function sairDaFila($usuario_id)
{
    DB::beginTransaction();

    try {
        
        $fila = Fila::where('usuario_id', $usuario_id)->first();

        if (!$fila) {
            return response()->json([
                'message' => 'Usuário não encontrado na fila.'
            ], 404);
        }

        $posicaoRemovida = $fila->posicao;

        
        $fila->delete();

       
        Fila::where('posicao', '>', $posicaoRemovida)
            ->decrement('posicao');

        DB::commit();

        return response()->json([
            'message' => 'Usuário removido da fila com sucesso!',
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Erro ao remover usuário da fila.',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
