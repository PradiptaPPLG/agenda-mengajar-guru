<?php

use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\SiswaProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allKelas = Kelas::withTrashed()->get();
        $grouped = $allKelas->groupBy(function ($k) {
            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $k->nama));
        });

        foreach ($grouped as $cleanKey => $classes) {
            if ($classes->count() <= 1) {
                continue;
            }

            // Choose primary class: prefer the one that has students, or is active
            $primary = $classes->sortByDesc(function ($k) {
                $score = 0;
                if (! $k->trashed()) {
                    $score += 100;
                }
                $score += SiswaProfile::where('kelas_id', $k->id)->count() * 10;
                $score += JadwalPelajaran::where('kelas_id', $k->id)->count();

                return $score;
            })->first();

            if (! $primary) {
                continue;
            }

            if ($primary->trashed()) {
                $primary->restore();
            }

            foreach ($classes as $dup) {
                if ($dup->id === $primary->id) {
                    continue;
                }

                // Move schedules
                JadwalPelajaran::where('kelas_id', $dup->id)->update(['kelas_id' => $primary->id]);

                // Move students
                SiswaProfile::where('kelas_id', $dup->id)->update(['kelas_id' => $primary->id]);

                // Move mapel pivot
                $dupMapelIds = DB::table('kelas_mata_pelajaran')->where('kelas_id', $dup->id)->pluck('mata_pelajaran_id');
                foreach ($dupMapelIds as $mId) {
                    DB::table('kelas_mata_pelajaran')->updateOrInsert(
                        ['kelas_id' => $primary->id, 'mata_pelajaran_id' => $mId],
                        ['updated_at' => now(), 'created_at' => now()]
                    );
                }
                DB::table('kelas_mata_pelajaran')->where('kelas_id', $dup->id)->delete();

                // Transfer wali/bk if primary doesn't have it
                if (! $primary->wali_kelas_id && $dup->wali_kelas_id) {
                    $primary->wali_kelas_id = $dup->wali_kelas_id;
                }
                if (! $primary->bk_id && $dup->bk_id) {
                    $primary->bk_id = $dup->bk_id;
                }
                $primary->save();

                // Delete duplicate class
                $dup->forceDelete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: merged classes are consolidated permanently
    }
};
