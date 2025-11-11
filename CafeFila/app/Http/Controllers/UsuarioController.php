<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    
    public function listar()
    {
        try {
            $usuarios = Usuario::all();
            return response()->json($usuarios, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

  
    public function buscarPorId(string $id)
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);
            }

            return response()->json($usuario, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar usuário.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    
    public function buscarPorEmail(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return response()->json([
                'message' => 'Parâmetro "email" é obrigatório na query string.'
            ], 400);
        }

        $usuario = Usuario::where('email', $email)->first();

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuário não encontrado.'
            ], 404);
        }

        return response()->json($usuario, 200);
    }

   
    public function criar(UsuarioRequest $request)
    {
        $dados = $request->all();

        try {
           
            $emailExistente = Usuario::where('email', $dados['email'])->exists();

            if ($emailExistente) {
                return response()->json([
                    "message" => "E-mail já cadastrado!"
                ], 400);
            }

            $usuario = new Usuario();
            $usuario->email = $dados["email"];
            $usuario->senha = Hash::make($dados["senha"]); 
            $usuario->admin = $dados["admin"] ?? false;
            $usuario->status = $dados["status"] ?? "ativo";
            $usuario->save();

            return response()->json([
                "message" => "Usuário criado com sucesso!",
                "usuario" => $usuario
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao criar usuário.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    
    public function atualizar(string $id, UsuarioRequest $request)
    {
        try {
            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    "message" => "Usuário não encontrado!"
                ], 404);
            }

            $dados = $request->only(['email', 'senha', 'admin', 'status']);

          
            $emailExistente = Usuario::where('email', $dados['email'])
                ->where('id', '!=', $id)
                ->exists();

            if ($emailExistente) {
                return response()->json([
                    "message" => "E-mail já está sendo usado por outro usuário!"
                ], 400);
            }

            $usuario->email = $dados["email"];

            
            if (!empty($dados["senha"])) {
                $usuario->senha = Hash::make($dados["senha"]);
            }

            $usuario->admin = $dados["admin"] ?? $usuario->admin;
            $usuario->status = $dados["status"] ?? $usuario->status;
            $usuario->save();

            return response()->json([
                "message" => "Usuário atualizado com sucesso!",
                "usuario" => $usuario
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao atualizar usuário.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    // 🔹 Login
    public function login(Request $request)
    {
        try {
            $dados = $request->validate([
                'email' => 'required|email',
                'senha' => 'required|string',
            ]);

            $usuario = Usuario::where('email', $dados['email'])->first();

            if (!$usuario) {
                return response()->json([
                    'message' => 'Usuário não encontrado.'
                ], 404);
            }

         
            if (!Hash::check($dados['senha'], $usuario->senha)) {
                return response()->json([
                    'message' => 'Senha incorreta.'
                ], 401);
            }

            return response()->json([
                'message' => 'Login realizado com sucesso!',
                'usuario' => $usuario,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao tentar fazer login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
