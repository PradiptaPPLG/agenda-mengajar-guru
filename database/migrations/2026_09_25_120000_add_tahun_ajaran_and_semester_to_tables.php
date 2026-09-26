<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah tahun_ajaran dan semester ke jadwal_pelajarans
        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->string('tahun_ajaran', 20)->default('2026/2027')->after('hari')->index();
            $table->string('semester', 10)->default('ganjil')->after('tahun_ajaran')->index();

            $table->index(['tahun_ajaran', 'semester'], 'jadwal_ta_sem_idx');
            $table->index(['kelas_id', 'tahun_ajaran', 'semester'], 'jadwal_kelas_ta_sem_idx');
        });

        // Backfill jadwal_pelajarans dengan tahun_ajaran dari kelas terkait
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE jadwal_pelajarans j
                INNER JOIN kelas k ON j.kelas_id = k.id
                SET j.tahun_ajaran = COALESCE(k.tahun_ajaran, '2026/2027'),
                    j.semester = 'ganjil'
                WHERE j.tahun_ajaran IS NULL OR j.tahun_ajaran = ''
            ");
        } else {
            DB::table('jadwal_pelajarans')
                ->whereNull('tahun_ajaran')
                ->orWhere('tahun_ajaran', '')
                ->update([
                    'tahun_ajaran' => '2026/2027',
                    'semester' => 'ganjil',
                ]);
        }

        // 2. Tambah tahun_ajaran dan semester ke pertemuans
        Schema::table('pertemuans', function (Blueprint $table) {
            $table->string('tahun_ajaran', 20)->nullable()->after('tanggal')->index();
            $table->string('semester', 10)->nullable()->after('tahun_ajaran')->index();

            $table->index(['tahun_ajaran', 'semester'], 'pertemuan_ta_sem_idx');
        });

        // Backfill pertemuans berdasarkan tanggal pertemuan:
        // Bulan 7-12 = Ganjil, Tahun Ajaran Y/(Y+1)
        // Bulan 1-6 = Genap, Tahun Ajaran (Y-1)/Y
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                UPDATE pertemuans
                SET semester = CASE WHEN MONTH(tanggal) >= 7 THEN 'ganjil' ELSE 'genap' END,
                    tahun_ajaran = CASE 
                        WHEN MONTH(tanggal) >= 7 THEN CONCAT(YEAR(tanggal), '/', YEAR(tanggal) + 1)
                        ELSE CONCAT(YEAR(tanggal) - 1, '/', YEAR(tanggal))
                    END
                WHERE tanggal IS NOT NULL AND (tahun_ajaran IS NULL OR tahun_ajaran = '')
            ");
        } else {
            DB::table('pertemuans')
                ->whereNotNull('tanggal')
                ->where(fn ($q) => $q->whereNull('tahun_ajaran')->orWhere('tahun_ajaran', ''))
                ->orderBy('id')
                ->chunk(500, function ($rows) {
                    foreach ($rows as $p) {
                        $month = (int) date('n', strtotime($p->tanggal));
                        $year = (int) date('Y', strtotime($p->tanggal));
                        $sem = $month >= 7 ? 'ganjil' : 'genap';
                        $ta = $month >= 7 ? ($year.'/'.($year + 1)) : (($year - 1).'/'.$year);

                        DB::table('pertemuans')->where('id', $p->id)->update([
                            'tahun_ajaran' => $ta,
                            'semester' => $sem,
                        ]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pertemuans', function (Blueprint $table) {
            $table->dropIndex('pertemuan_ta_sem_idx');
            $table->dropColumn(['tahun_ajaran', 'semester']);
        });

        Schema::table('jadwal_pelajarans', function (Blueprint $table) {
            $table->dropIndex('jadwal_ta_sem_idx');
            $table->dropIndex('jadwal_kelas_ta_sem_idx');
            $table->dropColumn(['tahun_ajaran', 'semester']);
        });
    }
};
