<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = [
        'order_id', // Pastikan ini ada
        'user_id',
        'mobil_id',
        'amount',   // Pastikan ini ada
        'payment_type',
        'payment_status',
        'snap_token',
        'payment_details',
    ];
}
