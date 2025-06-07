<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobilsTable extends Migration
{
    public function up()
    {
        Schema::create('mobils', function (Blueprint $table) {
            $table->bigIncrements('mobil_id'); // ✅ Ini cara mengganti id jadi mobil_id
            $table->string('nama');
            $table->string('merk');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobils');
    }
}