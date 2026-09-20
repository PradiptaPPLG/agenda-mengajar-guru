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
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('mata_pelajarans', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mata_pelajarans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('kelas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
