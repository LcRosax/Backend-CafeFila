<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilaRequest;
use App\Models\Fila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function adicionarPedido(Request $request, $item_type)
    {
        DB::beginTransaction();
        try {
            $usuarioLogado = JWTAuth::parseToken()->authenticate();
            
            if (!in_array($item_type, ['cafe', 'filtro'])) {
                return response()->json([
                    'message' => 'Tipo de item inválido. Use "cafe" ou "filtro".'
                ], 400);
            }

            $fila = Fila::where('usuario_id', $usuarioLogado->id)->first();
            $message = '';
            $status = 200;

            if ($fila) {
                $fila->{$item_type} = $fila->{$item_type} + 1;
                $fila->save();
                $message = 'Item "' . $item_type . '" adicionado ao pedido com sucesso!';

            } else {
                $ultimaPosicao = Fila::max('posicao') ?? 0;
                $novaPosicao = $ultimaPosicao + 1;

                $fila = Fila::create([
                    'usuario_id' => $usuarioLogado->id,
                    'posicao' => $novaPosicao,
                    'cafe' => $item_type === 'cafe' ? 1 : 0,
                    'filtro' => $item_type === 'filtro' ? 1 : 0,
                ]);
                
                $message = 'Usuário adicionado à fila e item "' . $item_type . '" adicionado ao pedido com sucesso!';
                $status = 201;
            }
            
            DB::commit();

            return response()->json([
                'message' => $message,
                'dados' => $fila,
            ], $status);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao adicionar item ao pedido.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function moverParaProximo(Request $request, $usuario_id)
    {
        DB::beginTransaction();
        try {
            $usuarioLogado = JWTAuth::parseToken()->authenticate();

            if (!$usuarioLogado || !$usuarioLogado->admin) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Acesso negado. Apenas administradores podem mover usuários na fila.'
                ], 403);
            }

            $filaMover = Fila::where('usuario_id', $usuario_id)->first();

            if (!$filaMover) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Usuário não encontrado na fila.'
                ], 404);
            }

            $posicaoAtual = $filaMover->posicao;
            
            if ($posicaoAtual <= 2) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Usuário já está na primeira ou segunda posição da fila. Nenhuma ação necessária.'
                ], 200);
            }
            
            Fila::whereBetween('posicao', [2, $posicaoAtual - 1])
                ->increment('posicao');
            
            $filaMover->posicao = 2;
            $filaMover->save();

            DB::commit();

            return response()->json([
                'message' => 'Usuário movido para a segunda posição da fila com sucesso!',
                'usuario_id' => $usuario_id,
                'nova_posicao' => 2
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erro ao mover usuário para a segunda posição.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function concluirEVoltarParaFinal(Request $request, $usuario_id)
    {
        $comprasController = app(ComprasController::class); 

        DB::beginTransaction();

        try {
            $fila = Fila::where('usuario_id', $usuario_id)->first();
            
            if (!$fila) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Usuário não encontrado na fila.'
                ], 404);
            }

            $primeiroDaFila = Fila::orderBy('posicao', 'asc')->first();
            if ($fila->id !== $primeiroDaFila->id) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Apenas o primeiro usuário da fila pode registrar a conclusão da compra.'
                ], 403);
            }

            $posicaoAntiga = $fila->posicao;
            $fila->delete();
            
            Fila::where('posicao', '>', $posicaoAntiga)->decrement('posicao');

            $novaPosicao = (Fila::max('posicao') ?? 0) + 1;
            
            Fila::create([
                'usuario_id' => $usuario_id,
                'posicao' => $novaPosicao,
                'cafe' => 0,
                'filtro' => 0,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Compra concluída e usuário movido para o final da fila com sucesso!',
                'nova_posicao' => $novaPosicao
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao concluir a compra e mover o usuário.',
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
                DB::rollBack();
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