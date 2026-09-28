<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kehadiran_siswas', function (Blueprint $table) {
            $table->string('status', 50)->default('hadir')->change();
        });
    }

    public function down(): void
    {
        Schema::table('kehadiran_siswas', function (Blueprint $table) {
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa', 'dispensasi'])->default('hadir')->change();
        });
    }
};
