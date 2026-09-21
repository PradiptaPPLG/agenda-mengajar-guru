<?php

namespace Database\Seeders;

use App\Models\PermissionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Bersihkan data permission & kategori lama agar sinkron
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Hapus relasi role-permission lama & permission/category lama
        DB::table('role_has_permissions')->delete();
        DB::table('model_has_permissions')->delete();
        Permission::query()->delete();
        PermissionCategory::query()->delete();

        // Kategori dan Permission KHUSUS Aplikasi Agenda Mengajar Guru
        $structure = [
            'Agenda & Jurnal Mengajar' => [
                ['name' => 'agenda.view', 'label' => 'Lihat Agenda & Jurnal Pertemuan'],
                ['name' => 'agenda.isi_materi', 'label' => 'Input / Edit Materi Pembelajaran'],
                ['name' => 'agenda.isi_penugasan', 'label' => 'Input / Edit Penugasan & Catatan'],
                ['name' => 'agenda.absen_siswa', 'label' => 'Catat Absensi Siswa Saat Mengajar'],
                ['name' => 'agenda.upload_bukti', 'label' => 'Unggah Foto Bukti KBM'],
            ],
            'Jadwal Pelajaran' => [
                ['name' => 'jadwal.view', 'label' => 'Lihat Jadwal Pelajaran'],
                ['name' => 'jadwal.manage', 'label' => 'Kelola Jadwal Pelajaran (Tambah/Edit/Hapus)'],
                ['name' => 'jadwal.import_export', 'label' => 'Import & Export Jadwal (Excel/PDF)'],
            ],
            'Data Akademik & Kelas' => [
                ['name' => 'akademik.manage_mapel', 'label' => 'Kelola Mata Pelajaran & Jam Pelajaran'],
                ['name' => 'akademik.manage_kelas', 'label' => 'Kelola Data Kelas & Rombel'],
                ['name' => 'akademik.sync_siswa', 'label' => 'Kelola Siswa di Kelas (Sync / Import)'],
                ['name' => 'akademik.manage_hari_libur', 'label' => 'Kelola Kalender & Hari Libur'],
            ],
            'Data Pengguna & Siswa' => [
                ['name' => 'users.manage_guru', 'label' => 'Kelola Data Guru & Staf'],
                ['name' => 'users.manage_siswa', 'label' => 'Kelola Data & Status Akun Siswa'],
                ['name' => 'users.import', 'label' => 'Import Data Pengguna/Siswa (Excel)'],
            ],
            'Piket & Pengawasan KBM' => [
                ['name' => 'piket.view_monitoring', 'label' => 'Monitoring Agenda & Kehadiran Guru Harian'],
                ['name' => 'piket.kirim_teguran', 'label' => 'Kirim Teguran ke Guru yang Belum Hadir/Isi Agenda'],
            ],
            'Pemantauan Kelas (Wali Kelas & BK)' => [
                ['name' => 'walikelas.view_rekap', 'label' => 'Lihat Rekap Absensi Siswa Kelas Binaan (Wali Kelas)'],
                ['name' => 'bk.view_rekap', 'label' => 'Pantau Kehadiran & Rekap Siswa Bermasalah (BK)'],
            ],
            'Kepala Program Keahlian (Kaprog)' => [
                ['name' => 'kaprog.view_rekap', 'label' => 'Pantau KBM & Kehadiran Kelas Jurusan (Kaprog)'],
            ],
            'Tata Usaha (TU)' => [
                ['name' => 'tu.view_rekap_absen', 'label' => 'Lihat & Filter Rekapitulasi Absensi Bulanan'],
            ],
            'Laporan & Rekapitulasi' => [
                ['name' => 'laporan.view_rekap_guru', 'label' => 'Lihat Rekap Jurnal & Agenda Guru'],
                ['name' => 'laporan.view_rekap_siswa', 'label' => 'Lihat Rekapitulasi Kehadiran Siswa'],
                ['name' => 'laporan.export_pdf', 'label' => 'Cetak Rekap Laporan ke PDF'],
                ['name' => 'laporan.export_excel', 'label' => 'Export Rekap Laporan ke Excel'],
            ],
        ];

        foreach ($structure as $categoryName => $permissions) {
            $category = PermissionCategory::create(['name' => $categoryName]);

            foreach ($permissions as $perm) {
                Permission::create([
                    'name' => $perm['name'],
                    'label' => $perm['label'],
                    'category_id' => $category->id,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Setup role tambahan operasional (Wali Kelas, Guru BK, Kaprog, Petugas Piket, Staf TU)
        $roleBk = Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        $roleBk->syncPermissions(['bk.view_rekap']);

        $roleWali = Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        $roleWali->syncPermissions(['walikelas.view_rekap']);

        $roleKaprog = Role::firstOrCreate(['name' => 'Kaprog', 'guard_name' => 'web']);
        $roleKaprog->syncPermissions(['kaprog.view_rekap']);

        $rolePiket = Role::firstOrCreate(['name' => 'Petugas Piket', 'guard_name' => 'web']);
        $rolePiket->syncPermissions(['piket.view_monitoring', 'piket.kirim_teguran']);

        $roleTu = Role::firstOrCreate(['name' => 'Staf TU', 'guard_name' => 'web']);
        $roleTu->syncPermissions(['tu.view_rekap_absen', 'laporan.view_rekap_guru', 'laporan.view_rekap_siswa', 'laporan.export_excel']);
    }
}
