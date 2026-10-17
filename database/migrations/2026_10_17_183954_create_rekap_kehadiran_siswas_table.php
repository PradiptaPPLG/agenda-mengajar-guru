<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekap_kehadiran_siswas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->string('tahun_ajaran', 20);
            $table->string('semester', 10);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('hadir')->default(0); // Kategori hadir: hadir + terlambat
            $table->unsignedInteger('hadir_murni')->default(0);
            $table->unsignedInteger('terlambat')->default(0);
            $table->unsignedInteger('sakit')->default(0);
            $table->unsignedInteger('izin')->default(0);
            $table->unsignedInteger('alpa')->default(0);
            $table->unsignedInteger('dispensasi')->default(0);
            $table->timestamps();

            $table->unique(['siswa_id', 'kelas_id', 'tahun_ajaran', 'semester'], 'rekap_siswa_unik_idx');
            $table->index(['kelas_id', 'tahun_ajaran', 'semester'], 'rekap_kelas_ta_sem_idx');
            $table->index(['tahun_ajaran', 'semester'], 'rekap_ta_sem_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_kehadiran_siswas');
    }
};
