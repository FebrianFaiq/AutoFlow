<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransaksisTable extends Migration
{
    public function up()
    {
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->unique(); // ID unik untuk Midtrans dan internal
            $table->bigInteger('user_id')->unsigned(); // Asumsi ada user_id dari Auth Service
            $table->bigInteger('mobil_id')->unsigned(); // Asumsi ada mobil_id
            $table->decimal('amount', 15, 2);
            $table->string('payment_type')->nullable();
            $table->string('payment_status')->default('pending'); // pending, success, failed, challenge, expire
            $table->string('snap_token')->nullable(); // Untuk menyimpan Snap token Midtrans
            $table->json('payment_details')->nullable(); // Untuk menyimpan detail respons dari Midtrans
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaksis');
    }
}