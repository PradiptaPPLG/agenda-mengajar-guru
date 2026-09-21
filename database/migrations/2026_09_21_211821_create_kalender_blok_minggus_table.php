<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kalender_blok_minggus', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('nomor_minggu')
                ->comment('Urutan minggu dari awal semester, dimulai dari 1');
            $table->string('label')
                ->comment('Label deskriptif, misal: Minggu ke-3 Semester Ganjil 2025/2026');
            $table->date('tanggal_mulai')
                ->comment('Tanggal Senin pada minggu tersebut');
            $table->date('tanggal_selesai')
                ->comment('Tanggal Sabtu pada minggu tersebut');
            $table->string('semester', 20)->default('ganjil')
                ->comment('Semester: ganjil atau genap');
            $table->string('tahun_ajaran', 10)
                ->comment('Tahun ajaran, misal: 2025/2026');
            $table->timestamps();

            $table->unique(['nomor_minggu', 'tahun_ajaran', 'semester'], 'unique_minggu_per_semester');
            $table->index(['tanggal_mulai', 'tanggal_selesai'], 'idx_rentang_tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kalender_blok_minggus');
    }
};
