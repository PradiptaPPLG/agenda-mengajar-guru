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
        if (! Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('tahun_ajaran', 20); // Contoh: 2024/2025
                $table->unsignedTinyInteger('semester'); // 1 = Ganjil, 2 = Genap
                $table->boolean('is_active')->default(false);
                $table->boolean('is_locked')->default(false); // Cut-off / Read-Only flag
                $table->string('keterangan')->nullable();
                $table->timestamps();

                $table->unique(['tahun_ajaran', 'semester']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
