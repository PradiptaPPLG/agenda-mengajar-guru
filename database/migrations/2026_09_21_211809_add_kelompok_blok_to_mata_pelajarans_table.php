<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mata_pelajarans', function (Blueprint $table) {
            $table->enum('kelompok_blok', ['kelompok_a', 'kelompok_b', 'reguler'])->default('reguler')->after('jenis')
                ->comment('Kelompok A = Umum/Normatif, Kelompok B = Produktif/Kejuruan, Reguler = tidak ikut sistem blok');
        });
    }

    public function down(): void
    {
        Schema::table('mata_pelajarans', function (Blueprint $table) {
            $table->dropColumn('kelompok_blok');
        });
    }
};
