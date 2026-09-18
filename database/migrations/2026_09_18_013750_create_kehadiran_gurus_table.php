<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kehadiran_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pertemuan_id')->constrained('pertemuans')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'sakit', 'alpa', 'dispensasi']);
            $table->enum('jenis_alpa', ['ada_tugas', 'tanpa_tugas', 'guru_pengganti'])->nullable();
            $table->string('guru_pengganti_nama')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamp('waktu_hadir')->nullable();
            $table->timestamps();

            $table->unique(['pertemuan_id', 'guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kehadiran_gurus');
    }
};
