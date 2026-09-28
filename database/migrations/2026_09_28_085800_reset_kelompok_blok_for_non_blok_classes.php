<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('jadwal_pelajarans')
            ->join('kelas', 'kelas.id', '=', 'jadwal_pelajarans.kelas_id')
            ->where(function ($q) {
                $q->where('kelas.is_sistem_blok', false)->orWhereNull('kelas.is_sistem_blok');
            })
            ->where('jadwal_pelajarans.kelompok_blok', '!=', 'reguler')
            ->update(['jadwal_pelajarans.kelompok_blok' => 'reguler']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
