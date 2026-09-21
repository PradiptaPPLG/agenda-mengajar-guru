<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->boolean('is_sistem_blok')->default(false)->after('bk_id')
                ->comment('Apakah kelas ini menggunakan sistem jadwal blok A/B');
            $table->enum('model_rotasi', ['rotasi_minggu', 'split_harian'])->nullable()->after('is_sistem_blok')
                ->comment('Model rotasi: rotasi per minggu atau split dalam sehari');
            $table->enum('blok_awal', ['kelompok_a', 'kelompok_b'])->nullable()->after('model_rotasi')
                ->comment('Kelompok yang aktif di minggu pertama semester');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropColumn(['is_sistem_blok', 'model_rotasi', 'blok_awal']);
        });
    }
};
