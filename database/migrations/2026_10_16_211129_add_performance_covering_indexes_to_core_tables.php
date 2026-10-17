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
        Schema::table('pertemuans', function (Blueprint $table) {
            $table->index('tanggal', 'idx_pertemuans_tanggal');
            $table->index(['tanggal', 'status'], 'idx_pertemuans_tanggal_status');
            $table->index(['tanggal', 'jadwal_id'], 'idx_pertemuans_tanggal_jadwal');
        });

        Schema::table('kehadiran_siswas', function (Blueprint $table) {
            $table->index(['pertemuan_id', 'siswa_id', 'status'], 'kehadiran_siswas_covering_idx');
            $table->index(['siswa_id', 'status'], 'kehadiran_siswas_siswa_status_idx');
        });

        Schema::table('kehadiran_gurus', function (Blueprint $table) {
            $table->index(['guru_id', 'status'], 'kehadiran_gurus_guru_status_idx');
            $table->index(['pertemuan_id', 'guru_id', 'status'], 'kehadiran_gurus_covering_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kehadiran_gurus', function (Blueprint $table) {
            $table->dropIndex('kehadiran_gurus_covering_idx');
            $table->dropIndex('kehadiran_gurus_guru_status_idx');
        });

        Schema::table('kehadiran_siswas', function (Blueprint $table) {
            $table->dropIndex('kehadiran_siswas_siswa_status_idx');
            $table->dropIndex('kehadiran_siswas_covering_idx');
        });

        Schema::table('pertemuans', function (Blueprint $table) {
            $table->dropIndex('idx_pertemuans_tanggal_jadwal');
            $table->dropIndex('idx_pertemuans_tanggal_status');
            $table->dropIndex('idx_pertemuans_tanggal');
        });
    }
};
