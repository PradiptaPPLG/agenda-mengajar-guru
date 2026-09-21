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
        Schema::table('siswa_profiles', function (Blueprint $table) {
            $table->enum('kelompok_blok', ['kelompok_a', 'kelompok_b'])->nullable()->after('kelas_id')
                ->comment('Kelompok blok spesifik untuk siswa di kelas split');
        });

        Schema::table('kelas', function (Blueprint $table) {
            // Ubah tipe data menjadi string agar lebih fleksibel menampung 'split' 
            // tanpa masalah constraint enum di beberapa database
            $table->string('blok_awal', 30)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa_profiles', function (Blueprint $table) {
            $table->dropColumn('kelompok_blok');
        });

        Schema::table('kelas', function (Blueprint $table) {
            // Rollback ke string juga, karena downgrade ke enum lebih berisiko hilangkan data.
            $table->string('blok_awal', 255)->nullable()->change();
        });
    }
};
