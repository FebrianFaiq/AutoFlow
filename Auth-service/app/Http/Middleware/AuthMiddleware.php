<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use App\Models\User;

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        // Ambil token dari header Authorization
        $authHeader = $request->header('Authorization');
        $token = null;

        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $token = str_replace('Bearer ', '', $authHeader);
        }

        // Jika tidak ada di header, coba dari bearerToken()
        if (!$token) {
            $token = $request->bearerToken();
        }

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            $credentials = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $user = User::find($credentials->sub);
            
            if (!$user) {
                return response()->json(['error' => 'User not found'], 401);
            }
            
            $request->auth = $user;
            
        } catch (Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}