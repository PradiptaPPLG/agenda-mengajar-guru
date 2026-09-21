<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=OFF;');
            DB::statement('CREATE TABLE IF NOT EXISTS mata_pelajarans_temp AS SELECT * FROM mata_pelajarans;');
            Schema::dropIfExists('mata_pelajarans');

            Schema::create('mata_pelajarans', function (Blueprint $table) {
                $table->id();
                $table->string('nama');
                $table->string('kode', 20)->unique();
                $table->string('jenis', 50)->default('umum');
                $table->timestamps();
                $table->softDeletes();
            });

            DB::statement('INSERT INTO mata_pelajarans (id, nama, kode, jenis, created_at, updated_at, deleted_at) SELECT id, nama, kode, jenis, created_at, updated_at, deleted_at FROM mata_pelajarans_temp;');
            Schema::dropIfExists('mata_pelajarans_temp');
            DB::statement('PRAGMA foreign_keys=ON;');
        } else {
            Schema::table('mata_pelajarans', function (Blueprint $table) {
                $table->string('jenis', 50)->default('umum')->change();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
