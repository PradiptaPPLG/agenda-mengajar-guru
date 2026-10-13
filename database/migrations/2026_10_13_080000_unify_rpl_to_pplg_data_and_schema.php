<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengintegrasikan dan menyatukan seluruh data jurusan RPL menjadi PPLG
     * (Kurikulum Merdeka SMKN 1 Ciamis: RPL telah resmi berganti menjadi PPLG).
     */
    public function up(): void
    {
        // 1. Sinkronisasi Data Tabel KELAS (11RPL -> 11PPLG, 12RPL -> 12PPLG, dst.)
        $rplClasses = DB::table('kelas')
            ->where('nama', 'LIKE', '%RPL%')
            ->get();

        foreach ($rplClasses as $k) {
            $newNama = preg_replace('/RPL/i', 'PPLG', $k->nama);

            // Cek apakah target kelas dengan nama baru sudah ada
            $targetClass = DB::table('kelas')
                ->where('nama', $newNama)
                ->where('id', '!=', $k->id)
                ->first();

            if (! $targetClass) {
                // Langsung ubah nama kelas
                DB::table('kelas')->where('id', $k->id)->update([
                    'nama' => $newNama,
                    'updated_at' => now(),
                ]);
            } else {
                // Jika sudah ada kelas target (misal hasil import baru), pindahkan semua relasi
                if (Schema::hasTable('siswa_profiles')) {
                    DB::table('siswa_profiles')
                        ->where('kelas_id', $k->id)
                        ->update(['kelas_id' => $targetClass->id]);
                }

                if (Schema::hasTable('jadwal_pelajarans')) {
                    DB::table('jadwal_pelajarans')
                        ->where('kelas_id', $k->id)
                        ->update(['kelas_id' => $targetClass->id]);
                }

                if (Schema::hasTable('kelas_pkls')) {
                    DB::table('kelas_pkls')
                        ->where('kelas_id', $k->id)
                        ->update(['kelas_id' => $targetClass->id]);
                }

                if (Schema::hasTable('kelas_mata_pelajaran')) {
                    $existingMapels = DB::table('kelas_mata_pelajaran')
                        ->where('kelas_id', $targetClass->id)
                        ->pluck('mata_pelajaran_id')
                        ->all();

                    DB::table('kelas_mata_pelajaran')
                        ->where('kelas_id', $k->id)
                        ->whereNotIn('mata_pelajaran_id', $existingMapels)
                        ->update(['kelas_id' => $targetClass->id]);

                    DB::table('kelas_mata_pelajaran')
                        ->where('kelas_id', $k->id)
                        ->delete();
                }

                // Hapus record kelas lama yang sudah digabung
                DB::table('kelas')->where('id', $k->id)->delete();
            }
        }

        // 2. Sinkronisasi Penugasan Kaprog di GURU PROFILES (RPL -> PPLG)
        if (Schema::hasTable('guru_profiles')) {
            DB::table('guru_profiles')
                ->whereRaw('UPPER(TRIM(kaprog_jurusan)) = ?', ['RPL'])
                ->update(['kaprog_jurusan' => 'PPLG']);

            // Jika ada format seperti "Kaprog RPL" atau teks lainnya yang mengandung RPL
            $guruProfilesRpl = DB::table('guru_profiles')
                ->where('kaprog_jurusan', 'LIKE', '%RPL%')
                ->get();

            foreach ($guruProfilesRpl as $gp) {
                $newJurusan = preg_replace('/RPL/i', 'PPLG', $gp->kaprog_jurusan);
                DB::table('guru_profiles')->where('id', $gp->id)->update([
                    'kaprog_jurusan' => $newJurusan,
                ]);
            }
        }

        // 3. Sinkronisasi Data MATA PELAJARAN
        if (Schema::hasTable('mata_pelajarans')) {
            $rplMapels = DB::table('mata_pelajarans')
                ->where('nama', 'LIKE', '%RPL%')
                ->orWhere('kode', 'LIKE', '%RPL%')
                ->get();

            foreach ($rplMapels as $mp) {
                $newNama = preg_replace('/RPL/i', 'PPLG', $mp->nama);
                if (stripos($newNama, 'Rekayasa Perangkat Lunak') !== false) {
                    $newNama = str_ireplace('Rekayasa Perangkat Lunak', 'Pengembangan Perangkat Lunak dan Gim', $newNama);
                }
                $newKode = preg_replace('/RPL/i', 'PPLG', $mp->kode);

                // Cek apakah ada mapel target dengan kode yang sama
                $existingMapel = DB::table('mata_pelajarans')
                    ->where('kode', $newKode)
                    ->where('id', '!=', $mp->id)
                    ->first();

                if (! $existingMapel) {
                    DB::table('mata_pelajarans')->where('id', $mp->id)->update([
                        'nama' => $newNama,
                        'kode' => $newKode,
                        'updated_at' => now(),
                    ]);
                } else {
                    // Alihkan relasi jadwal_pelajarans ke existingMapel jika ada konflik
                    if (Schema::hasTable('jadwal_pelajarans')) {
                        DB::table('jadwal_pelajarans')
                            ->where('mata_pelajaran_id', $mp->id)
                            ->update(['mata_pelajaran_id' => $existingMapel->id]);
                    }

                    if (Schema::hasTable('kelas_mata_pelajaran')) {
                        DB::table('kelas_mata_pelajaran')
                            ->where('mata_pelajaran_id', $mp->id)
                            ->delete();
                    }

                    if (Schema::hasTable('guru_mata_pelajaran')) {
                        DB::table('guru_mata_pelajaran')
                            ->where('mata_pelajaran_id', $mp->id)
                            ->delete();
                    }

                    DB::table('mata_pelajarans')->where('id', $mp->id)->delete();
                }
            }
        }

        // 4. Bersihkan data kehadiran siswa yang merujuk pada siswa yang sudah tidak ada di users
        if (Schema::hasTable('kehadiran_siswas') && Schema::hasTable('users')) {
            $hardDeletedUserIds = DB::table('kehadiran_siswas')
                ->leftJoin('users', 'kehadiran_siswas.siswa_id', '=', 'users.id')
                ->whereNull('users.id')
                ->pluck('kehadiran_siswas.id')
                ->all();

            if (! empty($hardDeletedUserIds)) {
                DB::table('kehadiran_siswas')->whereIn('id', $hardDeletedUserIds)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Rollback nama kelas ke format RPL
        if (Schema::hasTable('kelas')) {
            DB::table('kelas')->where('nama', '11PPLG')->update(['nama' => '11RPL']);
            DB::table('kelas')->where('nama', '12PPLG')->update(['nama' => '12RPL']);
        }

        // 2. Rollback kaprog jurusan
        if (Schema::hasTable('guru_profiles')) {
            DB::table('guru_profiles')->where('kaprog_jurusan', 'PPLG')->update(['kaprog_jurusan' => 'RPL']);
        }

        // 3. Rollback mata pelajaran ke format RPL
        if (Schema::hasTable('mata_pelajarans')) {
            DB::table('mata_pelajarans')->where('kode', 'KKPPLG-11')->update(['kode' => 'KKRPL-11', 'nama' => 'KK-RPL 11']);
            DB::table('mata_pelajarans')->where('kode', 'KKPPLG-12')->update(['kode' => 'KKRPL-12', 'nama' => 'KK-RPL 12']);
            DB::table('mata_pelajarans')->where('kode', 'PKK-PPLG')->update(['kode' => 'PKK-RPL', 'nama' => 'PRODUK KREATIF RPL']);
            DB::table('mata_pelajarans')->where('nama', 'MP-PPLG 11')->update(['nama' => 'MP-RPL 11']);
            DB::table('mata_pelajarans')->where('nama', 'MP-PPLG 12')->update(['nama' => 'MP-RPL 12']);
        }
    }
};
