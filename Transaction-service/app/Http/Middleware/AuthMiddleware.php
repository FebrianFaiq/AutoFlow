<?php

namespace App\Http\Middleware; // Pastikan namespace benar

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Illuminate\Support\Facades\Log; // Untuk logging

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        Log::info('[AuthMiddleware] Middleware dipanggil.');

        $token = $request->bearerToken(); // Mengambil token dari header "Authorization: Bearer <token>"

        if (!$token) {
            Log::warning('[AuthMiddleware] Token tidak ditemukan di header.');
            return response()->json(['status' => 'error', 'message' => 'Token not provided'], 401);
        }
        Log::info('[AuthMiddleware] Token ditemukan: ' . substr($token, 0, 15) . '...'); // Log sebagian token

        try {
            $jwtSecret = env('JWT_SECRET');
            if (!$jwtSecret) {
                Log::error('[AuthMiddleware] JWT_SECRET tidak diset di .env.');
                return response()->json(['status' => 'error', 'message' => 'JWT Secret not configured on server'], 500);
            }

            // Decode token. 'HS256' adalah algoritma yang umum digunakan. Sesuaikan jika berbeda.
            // JWT::decode mengembalikan objek (stdClass)
            $credentials = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            Log::info('[AuthMiddleware] Token berhasil di-decode. Payload:', (array) $credentials);

        } catch (ExpiredException $e) {
            Log::warning('[AuthMiddleware] Token kedaluwarsa: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Provided token is expired'], 400);
        } catch (SignatureInvalidException $e) {
            Log::warning('[AuthMiddleware] Signature token tidak valid: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Provided token has an invalid signature'], 401);
        } catch (BeforeValidException $e) {
            Log::warning('[AuthMiddleware] Token belum valid (nbf claim): ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Provided token is not yet valid'], 401);
        } catch (Exception $e) {
            Log::error('[AuthMiddleware] Error saat decode token: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'An error occurred while decoding token'], 400);
        }

        // Tambahkan data pengguna (payload token) ke request agar bisa diakses di controller
        $request->auth = $credentials; // $credentials adalah objek stdClass dari payload token
        Log::info('[AuthMiddleware] Payload token ditambahkan ke $request->auth.');

        return $next($request);
    }
}