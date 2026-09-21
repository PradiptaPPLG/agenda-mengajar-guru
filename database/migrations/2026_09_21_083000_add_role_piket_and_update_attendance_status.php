<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 50)->default('siswa')->change();
        });

        Schema::table('kehadiran_gurus', function (Blueprint $table) {
            $table->string('status', 50)->default('hadir')->change();
            $table->string('alasan_tidak_hadir', 50)->nullable()->after('status');
        });

        Schema::table('foto_buktis', function (Blueprint $table) {
            $table->string('status_guru_dilaporkan', 50)->default('hadir')->change();
            $table->string('alasan_tidak_hadir', 50)->nullable()->after('status_guru_dilaporkan');
        });
    }

    public function down(): void
    {
        Schema::table('foto_buktis', function (Blueprint $table) {
            $table->dropColumn('alasan_tidak_hadir');
        });

        Schema::table('kehadiran_gurus', function (Blueprint $table) {
            $table->dropColumn('alasan_tidak_hadir');
        });
    }
};
