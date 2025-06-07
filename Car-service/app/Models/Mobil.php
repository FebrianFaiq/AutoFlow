<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mobil extends Model
{
    protected $primaryKey = 'mobil_id'; // tambahkan ini

    protected $fillable = [
        'nama',
        'merk',
        'deskripsi',
        'amount',
        'gambar'
    ];

}

