<?php
namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

JWT::$leeway = 60; // Toleransi 60 detik

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        // 1. Ambil token dari header Authorization: Bearer <token>
        $token = $request->bearerToken();

        if (!$token) {
            // Jika tidak ada token, tolak request
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            // 2. Decode token menggunakan JWT_SECRET yang ada di .env
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            // dd($decoded);
            // Hasil decode: objek payload JWT (biasanya berisi sub, email, exp, dsb)
            // Simpan data user_id (sub) dan email ke dalam $request->auth untuk dipakai Controller
            $request->auth = (object) [
                'id'    => $decoded->sub,
                // 'email' => $decoded->email,
            ];
        } catch (\Exception $e) {
            // Jika decode gagal (token invalid, expired, signature mismatch, dll):
            return response()->json([
                'error'   => 'Invalid token',
                'message' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
