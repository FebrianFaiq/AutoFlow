<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeskripsiDanGambarToMobilsTable extends Migration
{
    public function up()
    {
        Schema::table('mobils', function (Blueprint $table) {
            // Tambahkan kolom deskripsi (nullable) setelah kolom merk
            $table->text('deskripsi')->nullable()->after('merk');
            // Tambahkan kolom gambar (nullable) setelah kolom amount
            $table->string('gambar')->nullable()->after('amount');
        });
    }

    public function down()
    {
        Schema::table('mobils', function (Blueprint $table) {
            $table->dropColumn(['deskripsi', 'gambar']);
        });
    }
}
