<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foto_buktis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pertemuan_id')->constrained('pertemuans')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('users')->cascadeOnDelete();
            $table->string('foto_path');
            $table->enum('status_guru_dilaporkan', ['hadir', 'sakit', 'alpa', 'dispensasi']);
            $table->enum('jenis_alpa_dilaporkan', ['ada_tugas', 'tanpa_tugas', 'guru_pengganti'])->nullable();
            $table->string('guru_pengganti_nama')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_buktis');
    }
};
