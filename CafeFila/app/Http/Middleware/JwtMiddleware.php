<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            // Mostra todos os headers no log
            logger('All headers: ' . json_encode($request->headers->all()));

            // Tenta pegar o token do header Authorization ou X-Authorization
            $token = $request->header('Authorization') ?? $request->header('X-Authorization');

            if (!$token) {
                return response()->json(['error' => 'Token ausente'], 401);
            }

            // Remove "Bearer " se existir
            $token = str_replace('Bearer ', '', $token);

            // Autentica o usuário com JWTAuth
            $usuario = JWTAuth::setToken($token)->authenticate();

            if (!$usuario) {
                return response()->json(['error' => 'Usuário não encontrado no token'], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Token inválido ou expirado',
                'details' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
