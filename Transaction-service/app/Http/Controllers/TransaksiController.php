<?php

namespace App\Http\Controllers; // Pastikan namespace benar

use App\Models\Transaksi; // Pastikan model diimport
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Untuk logging
use Illuminate\Support\Str;
use Midtrans\Config as MidtransConfig;
use Midtrans\Snap as MidtransSnap;
// use Midtrans\Notification as MidtransNotification; // Sudah di-handle di methodnya

class TransaksiController extends Controller
{
    public function __construct()
    {
        // Konfigurasi Midtrans
        $serverKey = config('midtrans.server_key');
        $clientKey = config('midtrans.client_key');

        if (!$serverKey || !$clientKey) {
            Log::error('[TransaksiController] Midtrans ServerKey atau ClientKey tidak diset di config.');
        }

        MidtransConfig::$serverKey = $serverKey;
        MidtransConfig::$clientKey = $clientKey;
        MidtransConfig::$isProduction = config('midtrans.is_production');
        MidtransConfig::$isSanitized = config('midtrans.is_sanitized');
        MidtransConfig::$is3ds = config('midtrans.is_3ds');
    }

    public function beli(Request $request)
    {
        Log::info('[TransaksiController@beli] Request diterima.');

        // 1. Ambil objek 'auth' dari request (hasil decode JWT oleh middleware)
        if (!property_exists($request, 'auth') || $request->auth === null) {
            Log::error('[TransaksiController@beli] $request->auth tidak ditemukan atau null. Middleware mungkin gagal.');
            return response()->json(['status' => 'error', 'message' => 'Data autentikasi tidak ditemukan (middleware error).'], 500);
        }
        $userAuth = $request->auth; // Ini adalah objek stdClass dari payload token
        Log::info('[TransaksiController@beli] Isi $request->auth:', (array) $userAuth);

        // 2. Ambil user_id dari klaim 'sub' di payload token
        // JWT.IO Anda menunjukkan ada 'sub', jadi kita gunakan 'sub'.
        if (!property_exists($userAuth, 'sub') || $userAuth->sub === null) {
            Log::error('[TransaksiController@beli] Klaim "sub" (user_id) tidak ditemukan di $request->auth atau nilainya null.');
            return response()->json(['status' => 'error', 'message' => 'User ID (sub claim) tidak ditemukan dalam token atau null.'], 400);
        }
        $userId = $userAuth->sub;
        Log::info('[TransaksiController@beli] User ID dari token (sub claim): ' . $userId);

        // 3. Validasi input request (mobil_id, amount, dll. TIDAK ADA user_id dari body)
        $this->validate($request, [
            'mobil_id' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'customer_details' => 'sometimes|array', // Opsional dari API Gateway
            'item_details' => 'sometimes|array',   // Opsional dari API Gateway
        ]);
        Log::info('[TransaksiController@beli] Validasi input berhasil.');

        // 4. Buat Order ID
        $orderId = 'ORDER-' . Str::uuid()->toString();

        // 5. Data untuk disimpan ke database
        $dataToCreate = [
            'order_id' => $orderId,
            'user_id' => $userId, // Pastikan $userId tidak null di sini
            'mobil_id' => $request->input('mobil_id'),
            'amount' => $request->input('amount'),
            'payment_status' => 'pending',
        ];
        Log::info('[TransaksiController@beli] Data untuk create transaksi:', $dataToCreate);

        if (is_null($dataToCreate['user_id'])) {
             Log::critical('[TransaksiController@beli] FATAL: user_id akan disimpan sebagai NULL. Menghentikan.');
             return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan internal: user_id bernilai null sebelum disimpan.'], 500);
        }

        $transaksi = Transaksi::create($dataToCreate);
        Log::info('[TransaksiController@beli] Transaksi berhasil dibuat di DB. Order ID: ' . $orderId);

        // 6. Siapkan parameter untuk Midtrans
        // Ambil detail customer dari token jika ada, atau dari request jika API Gateway meneruskannya
        $customerDetails = $request->input('customer_details', [
            'first_name' => $userAuth->name ?? 'Pengguna', // Asumsi ada 'name' di token
            'email' => $userAuth->email ?? 'email@example.com', // Asumsi ada 'email' di token
            'phone' => $userAuth->phone ?? '08123456789',      // Asumsi ada 'phone' di token
        ]);

        $itemDetails = $request->input('item_details', [[
            'id' => (string) $request->input('mobil_id'),
            'price' => (int) $request->input('amount'),
            'quantity' => 1,
            'name' => 'Pembelian Mobil ID: ' . $request->input('mobil_id'),
        ]]);
        
        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $transaksi->amount,
            ],
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
        ];
        Log::info('[TransaksiController@beli] Parameter untuk Midtrans:', $params);

        try {
            $snapToken = MidtransSnap::getSnapToken($params);
            Log::info('[TransaksiController@beli] Snap token Midtrans berhasil didapatkan.');

            $transaksi->snap_token = $snapToken;
            $transaksi->save();

            return response()->json([
                'status' => 'success',
                'order_id' => $orderId,
                'snap_token' => $snapToken,
                'redirect_url' => MidtransSnap::getSnapUrl($params)
            ]);

        } catch (Exception $e) {
            Log::error('[TransaksiController@beli] Error mendapatkan Snap Token Midtrans: ' . $e->getMessage(), ['params' => $params]);
            $transaksi->payment_status = 'failed_to_initiate_payment';
            $transaksi->payment_details = ['error_midtrans' => $e->getMessage()];
            $transaksi->save();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memulai pembayaran dengan Midtrans: ' . $e->getMessage()
            ], 500);
        }
    }

    public function riwayat(Request $request)
    {
        Log::info('[TransaksiController@riwayat] Request diterima.');

        if (!property_exists($request, 'auth') || $request->auth === null) {
            Log::error('[TransaksiController@riwayat] $request->auth tidak ditemukan atau null.');
            return response()->json(['status' => 'error', 'message' => 'Data autentikasi tidak ditemukan.'], 500);
        }
        $userAuth = $request->auth;
        Log::info('[TransaksiController@riwayat] Isi $request->auth:', (array) $userAuth);

        if (!property_exists($userAuth, 'sub') || $userAuth->sub === null) {
            Log::error('[TransaksiController@riwayat] Klaim "sub" (user_id) tidak ditemukan di $request->auth atau nilainya null.');
            return response()->json(['status' => 'error', 'message' => 'User ID (sub claim) tidak ditemukan dalam token atau null.'], 400);
        }
        $userId = $userAuth->sub;
        Log::info('[TransaksiController@riwayat] User ID dari token (sub claim): ' . $userId);

        $transaksis = Transaksi::where('user_id', $userId)
                               ->orderBy('created_at', 'desc')
                               ->get();
        Log::info('[TransaksiController@riwayat] Riwayat transaksi ditemukan sejumlah: ' . $transaksis->count());

        return response()->json($transaksis);
    }

    public function handleNotification(Request $request)
    {
        Log::info('[TransaksiController@handleNotification] Notifikasi Midtrans diterima:', $request->all());
        $notif = null; // Inisialisasi
        try {
            // Parameter true untuk menginstruksikan agar mengambil dari php://input dan melakukan sanitasi
            $notif = new \Midtrans\Notification();
        } catch (Exception $e) {
            Log::error('[TransaksiController@handleNotification] Error membuat objek MidtransNotification: ' . $e->getMessage(), $request->all());
            return response()->json(['status' => 'error', 'message' => 'Invalid notification object from Midtrans.'], 400);
        }

        if (!$notif) {
             Log::error('[TransaksiController@handleNotification] Objek notifikasi Midtrans null setelah instansiasi.');
             return response()->json(['status' => 'error', 'message' => 'Failed to process Midtrans notification object.'], 500);
        }

        $transactionStatus = $notif->transaction_status;
        $paymentType = $notif->payment_type;
        $orderId = $notif->order_id;
        $fraudStatus = $notif->fraud_status;

        Log::info("[TransaksiController@handleNotification] Order ID: {$orderId}, Status: {$transactionStatus}, Payment Type: {$paymentType}, Fraud Status: {$fraudStatus}");

        $transaksi = Transaksi::where('order_id', $orderId)->first();

        if (!$transaksi) {
            Log::warning("[TransaksiController@handleNotification] Transaksi dengan Order ID: {$orderId} tidak ditemukan.");
            // Penting: Midtrans mengharapkan respons 200 OK agar tidak mengirim notifikasi berulang
            // Meskipun transaksi tidak ditemukan di sisi kita, kita tetap harus merespons 200 OK
            // kecuali jika ada masalah fundamental dengan notifikasi itu sendiri.
            return response()->json(['status' => 'ok', 'message' => 'Notification received, but transaction not found locally. Midtrans should stop retrying.'], 200);
        }

        // Di sini Anda TIDAK PERLU memvalidasi signature_key secara manual JIKA Anda menggunakan
        // $notif = new \Midtrans\Notification();
        // karena library Midtrans sudah melakukannya secara internal.
        // Jika Anda ingin melakukan verifikasi manual (tidak disarankan jika sudah pakai library):
        // $localSignatureKey = hash("sha512", $orderId.$notif->status_code.$notif->gross_amount.config('midtrans.server_key'));
        // if ($notif->signature_key !== $localSignatureKey) { ... }

        $currentStatus = $transaksi->payment_status;
        if (in_array($currentStatus, ['success', 'settlement', 'capture'])) {
            Log::info("[TransaksiController@handleNotification] Transaksi Order ID: {$orderId} sudah dalam status {$currentStatus}. Notifikasi diabaikan untuk mencegah duplikasi proses.");
            return response()->json(['status' => 'ok', 'message' => 'Transaction already processed. Notification acknowledged.'], 200);
        }

        // Update status transaksi berdasarkan notifikasi
        $newStatus = $transaksi->payment_status; // default ke status lama
        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $newStatus = 'challenge';
            } else if ($fraudStatus == 'accept') {
                $newStatus = 'success'; // Atau 'capture' jika Anda ingin bedakan
            }
        } else if ($transactionStatus == 'settlement') {
            $newStatus = 'success'; // 'settlement' umumnya adalah status akhir yang sukses
        } else if ($transactionStatus == 'pending') {
            $newStatus = 'pending';
        } else if ($transactionStatus == 'deny') {
            $newStatus = 'denied';
        } else if ($transactionStatus == 'expire') {
            $newStatus = 'expired';
        } else if ($transactionStatus == 'cancel') {
            $newStatus = 'cancelled';
        } else {
            Log::warning("[TransaksiController@handleNotification] Status transaksi tidak dikenal: {$transactionStatus} untuk Order ID: {$orderId}");
        }

        $transaksi->payment_status = $newStatus;
        $transaksi->payment_type = $paymentType;
        // Simpan seluruh payload notifikasi untuk audit atau debugging di masa depan
        $transaksi->payment_details = (array) $notif->getResponse(); // Casting objek ke array
        $transaksi->save();

        Log::info("[TransaksiController@handleNotification] Transaksi Order ID: {$orderId} berhasil diupdate menjadi {$transaksi->payment_status}.");
        
        // TODO: Tambahkan logika bisnis setelah pembayaran berhasil/gagal
        // Misalnya: kirim email, update inventory, panggil service lain, dll.

        return response()->json(['status' => 'ok', 'message' => 'Notification processed successfully by Transaksi Service.'], 200);
    }
}