<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bug 6 fix: Add unique constraint on (nomor_minggu, tahun_ajaran, semester)
 * to prevent overlapping/duplicate weeks that cause ambiguous kelompok aktif.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kalender_blok_minggus', function (Blueprint $table) {
            $table->unique(['nomor_minggu', 'tahun_ajaran', 'semester'], 'kalender_blok_unique');
        });
    }

    public function down(): void
    {
        Schema::table('kalender_blok_minggus', function (Blueprint $table) {
            $table->dropUnique('kalender_blok_unique');
        });
    }
};
