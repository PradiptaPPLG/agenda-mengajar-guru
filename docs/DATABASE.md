# Cetak Biru Basis Data & Diagram Relasi (ERD)

Dokumen ini mendokumentasikan skema tabel, relasi antar-entitas, dan kebijakan integritas data (*soft deletes* & *cascades*).

---

## 📊 Diagram Visual Entity-Relationship (Mermaid ERD)

```mermaid
erDiagram
    USERS ||--o{ GURU_PROFILES : has_one
    USERS ||--o{ SISWA_PROFILES : has_one
    USERS ||--o{ JADWAL_PELAJARANS : teaches
    USERS ||--o{ KEHADIRAN_GURUS : attends
    USERS ||--o{ KEHADIRAN_SISWAS : attends
    USERS ||--o{ FOTO_BUKTIS : submits

    KELAS ||--o{ SISWA_PROFILES : contains
    KELAS ||--o{ JADWAL_PELAJARANS : schedules
    MATA_PELAJARAN ||--o{ JADWAL_PELAJARANS : categorized_as

    JADWAL_PELAJARANS ||--o{ PERTEMUANS : generates
    PERTEMUANS ||--o{ KEHADIRAN_GURUS : has
    PERTEMUANS ||--o{ KEHADIRAN_SISWAS : records
    PERTEMUANS ||--o{ FOTO_BUKTIS : verifies

    USERS {
        bigint id PK
        string name
        string email
        string password
        enum role "super_admin, admin, kepala_sekolah, guru, siswa"
        boolean is_active
        timestamp deleted_at "SoftDeletes"
    }

    KELAS {
        bigint id PK
        string nama
        string tingkat
        string tahun_ajaran
        timestamp deleted_at "SoftDeletes"
    }

    MATA_PELAJARAN {
        bigint id PK
        string nama
        string kode
        enum jenis "umum, kejuruan"
        timestamp deleted_at "SoftDeletes"
    }

    JADWAL_PELAJARANS {
        bigint id PK
        bigint kelas_id FK
        bigint guru_id FK
        bigint mata_pelajaran_id FK
        tinyint hari "1=Senin..6=Sabtu"
        time jam_mulai
        time jam_selesai
        timestamp deleted_at "SoftDeletes"
    }

    PERTEMUANS {
        bigint id PK
        bigint jadwal_id FK
        date tanggal
        text materi_ajar
        text penugasan
        enum status "menunggu, berlangsung, selesai"
    }

    KEHADIRAN_GURUS {
        bigint id PK
        bigint pertemuan_id FK
        bigint guru_id FK
        enum status "hadir, sakit, alpa, dispensasi"
        enum jenis_alpa "ada_tugas, tanpa_tugas, guru_pengganti"
        string guru_pengganti_nama
        text keterangan
        timestamp waktu_hadir
    }

    KEHADIRAN_SISWAS {
        bigint id PK
        bigint pertemuan_id FK
        bigint siswa_id FK
        enum status "hadir, sakit, izin, alpa, dispensasi"
        text keterangan
    }

    FOTO_BUKTIS {
        bigint id PK
        bigint pertemuan_id FK
        bigint siswa_id FK
        string foto_path
        enum status_guru_dilaporkan "hadir, sakit, alpa, dispensasi"
        enum jenis_alpa_dilaporkan
        string guru_pengganti_nama
    }

    HARI_LIBURS {
        bigint id PK
        date tanggal "unique"
        string keterangan
        enum jenis "nasional, cuti_bersama, khusus"
        timestamp created_at
        timestamp updated_at
    }
```

---

## 🛡️ Kebijakan Integritas & Keamanan Data

1. **Soft Deletes**:
   - `users`: Siswa lulus atau guru mutasi tidak menghapus rekap kehadiran masa lampau.
   - `jadwal_pelajarans`: Pergantian semester / perubahan jadwal tidak menghapus jurnal mengajar sebelumnya.
   - `kelas` & `mata_pelajarans`: Mengamankan data master dari penghapusan tidak sengaja.
2. **Transaksi Database**:
   - Operasi penulisan kehadiran guru, siswa, dan materi wajib berada dalam lingkup transaksi atomik (`DB::transaction`).
3. **Validasi Anti-Bentrok Waktu**:
   - Jadwal dengan `hari` yang sama tidak boleh memiliki rentang waktu tumpang tindih untuk `guru_id` yang sama atau `kelas_id` yang sama.
