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
        Schema::create('pengumumans', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 255);
            $table->text('isi');
            $table->enum('tipe', ['info', 'penting', 'urgent'])->default('info');
            $table->json('target_roles')->nullable(); // ['all'] or ['guru', 'siswa', etc.]
            $table->string('target_tingkat', 10)->nullable()->default('all'); // all, 10, 11, 12
            $table->string('file_lampiran', 255)->nullable();
            $table->string('nama_file', 255)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('tanggal_mulai')->useCurrent();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'tanggal_mulai', 'tanggal_selesai'], 'pengumuman_active_schedule_idx');
            $table->index(['is_pinned', 'created_at'], 'pengumuman_pinned_idx');
        });

        Schema::create('pengumuman_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengumuman_id')->constrained('pengumumans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['pengumuman_id', 'user_id'], 'pengumuman_user_read_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengumuman_reads');
        Schema::dropIfExists('pengumumans');
    }
};
