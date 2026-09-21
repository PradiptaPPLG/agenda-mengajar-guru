<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->enum('kelompok_blok', ['kelompok_a', 'kelompok_b', 'reguler'])->default('reguler')->after('jam_selesai')
                ->comment('Mewarisi dari mata_pelajaran.kelompok_blok, dapat di-override per jadwal');
        });
    }

    public function down(): void
    {
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->dropColumn('kelompok_blok');
        });
    }
};
