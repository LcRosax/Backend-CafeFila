<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;

class UsuarioController extends Controller
{
    public function me()
    {
        try {
            $usuario = JWTAuth::parseToken()->authenticate();

            if (!$usuario) {
                return response()->json(['message' => 'Usuário não encontrado.'], 404);
            }

            return response()->json([
                'id' => $usuario->id,
                'email' => $usuario->email,
                'admin' => $usuario->admin,
                'status' => $usuario->status,
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token inválido.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token de autenticação não fornecido.'], 401);
        } catch (\Exception $e) {
             return response()->json(['message' => 'Erro interno ao recuperar usuário.', 'error' => $e->getMessage()], 500);
        }
    }


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
        $dados = $request->validated();

        try {
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
            $usuarioLogado = JWTAuth::parseToken()->authenticate();

            if ($usuarioLogado->id != $id && !$usuarioLogado->admin) {
                return response()->json([
                    "message" => "Você não tem permissão para atualizar este usuário."
                ], 403);
            }

            $usuario = Usuario::find($id);

            if (!$usuario) {
                return response()->json([
                    "message" => "Usuário não encontrado!"
                ], 404);
            }

            $dados = $request->only(['email', 'senha', 'admin', 'status']);
            
            if (isset($dados['email']) && $dados['email'] !== $usuario->email) {
                 $emailExistente = Usuario::where('email', $dados['email'])
                     ->where('id', '!=', $id)
                     ->exists();

                if ($emailExistente) {
                    return response()->json([
                        "message" => "E-mail já está sendo usado por outro usuário!"
                    ], 400);
                }
                $usuario->email = $dados["email"];
            }

            if (!empty($dados["senha"])) {
                $usuario->senha = Hash::make($dados["senha"]);
            }
            
            if ($usuarioLogado->admin || $usuarioLogado->id == $id) {
                if ($usuarioLogado->admin) {
                    $usuario->admin = $dados["admin"] ?? $usuario->admin;
                    $usuario->status = $dados["status"] ?? $usuario->status;
                }
            }

            $usuario->save();

            return response()->json([
                "message" => "Usuário atualizado com sucesso!",
                "usuario" => $usuario
            ], 200);

        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expirado.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token inválido.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token de autenticação não fornecido.'], 401);
        } catch (\Exception $e) {
            return response()->json([
                "message" => "Erro ao atualizar usuário.",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $dados = $request->validate([
                'email' => 'required|email',
                'senha' => 'required|string',
            ]);

            $usuario = Usuario::where('email', $dados['email'])->first();

            if (!$usuario || !Hash::check($dados['senha'], $usuario->senha)) {
                return response()->json([
                    'message' => 'Credenciais inválidas.'
                ], 401);
            }

            $token = JWTAuth::fromUser($usuario);

            return response()->json([
                'message' => 'Login realizado com sucesso!',
                'usuario' => $usuario,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao tentar fazer login.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}