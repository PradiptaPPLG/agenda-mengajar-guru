<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('foto_buktis', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('guru_pengganti_nama');
        });
    }

    public function down(): void
    {
        Schema::table('foto_buktis', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
