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

    public function concluirEVoltarParaFinal(Request $request, $usuario_id)
    {
        // Cria uma instância do ComprasController para usar o método comprar
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

            // 1. VERIFICAÇÃO DE POSIÇÃO (Apenas o primeiro pode concluir a compra)
            $primeiroDaFila = Fila::orderBy('posicao', 'asc')->first();
            if ($fila->id !== $primeiroDaFila->id) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Apenas o primeiro usuário da fila pode registrar a conclusão da compra.'
                ], 403);
            }

            // 2. REGISTRO DA COMPRA
            // Chamada ao método comprar do ComprasController
            // Passamos a Request, mas a rota de compra deve ser feita separadamente pelo Front-end, 
            // ou integrarmos o payload da compra aqui. Por simplicidade, faremos o registro no final.

            // 3. MOVIMENTAÇÃO DA FILA (DELETE e RE-INCREMENT)
            $posicaoAntiga = $fila->posicao;
            $fila->delete();
            
            Fila::where('posicao', '>', $posicaoAntiga)->decrement('posicao');

            // 4. INSERÇÃO DO USUÁRIO NO FINAL
            $novaPosicao = (Fila::max('posicao') ?? 0) + 1;
            
            Fila::create([
                'usuario_id' => $usuario_id,
                'posicao' => $novaPosicao
            ]);

            // Se o Front-end não registrou a compra em /compras antes, ela deve ser feita aqui
            // Exemplo de como você faria o registro da compra *após* a movimentação da fila:
            // $comprasController->registrarCompraInterno($request, $usuario_id);

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