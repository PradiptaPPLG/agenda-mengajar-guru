<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Jadwal KBM Terlewat - {{ $schoolName }}</title>
<style>
    @page {
        margin: 25px 30px;
    }
    body {
        font-family: 'Helvetica', 'Arial', sans-serif;
        font-size: 10px;
        color: #1e293b;
        margin: 0;
        padding: 0;
        background-color: #ffffff;
    }
    
    .top-bar {
        height: 5px;
        background-color: #e11d48;
        margin-bottom: 15px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 12px;
    }
    .school-title {
        font-size: 16px;
        font-weight: bold;
        color: #be123c;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .doc-title {
        font-size: 14px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 3px;
    }
    .meta-info {
        font-size: 10px;
        color: #475569;
        margin-top: 6px;
        line-height: 1.4;
    }
    .meta-badge {
        display: inline-block;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: 600;
        color: #334155;
    }

    .summary-box {
        margin-bottom: 15px;
        padding: 10px;
        background-color: #fff1f2;
        border: 1px solid #fecdd3;
        border-radius: 6px;
        font-size: 10px;
        color: #9f1239;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .data-table th {
        background-color: #1e293b;
        color: #ffffff;
        text-align: center;
        padding: 7px 8px;
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border: 1px solid #1e293b;
    }
    .data-table th.th-left { text-align: left; }
    .data-table td {
        padding: 6px 8px;
        border: 1px solid #e2e8f0;
        text-align: center;
        font-size: 9px;
    }
    .data-table td.td-left { text-align: left; }
    .data-table tr:nth-child(even) td { background-color: #f8fafc; }

    .badge-unfilled {
        background-color: #fee2e2;
        color: #991b1b;
        font-weight: bold;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 8.5px;
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        page-break-inside: avoid;
    }
    .sig-box {
        width: 220px;
        float: right;
        text-align: center;
        font-size: 10px;
        color: #334155;
    }
    .sig-space {
        height: 45px;
    }
    .sig-name {
        font-weight: bold;
        text-decoration: underline;
        color: #0f172a;
    }

    .doc-footer {
        clear: both;
        padding-top: 15px;
        margin-top: 30px;
        border-top: 1px solid #e2e8f0;
        font-size: 8.5px;
        color: #94a3b8;
    }
</style>
</head>
<body>

<div class="top-bar"></div>

<table class="header-table">
    <tr>
        <td>
            <div class="school-title">{{ $schoolName }}</div>
            <div class="doc-title">Laporan Rekapitulasi Jadwal KBM Terlewat (Belum Diisi Guru)</div>
            <div class="meta-info">
                <strong>Periode Pemantauan:</strong> <span class="meta-badge">{{ $startDate->format('d M Y') }} – {{ $endDate->format('d M Y') }}</span> &nbsp;|&nbsp;
                <strong>Total Sesi Terlewat:</strong> <span class="meta-badge" style="background:#fee2e2; color:#991b1b; border-color:#fca5a5;">{{ number_format($totalTerlewat) }} Jam Pelajaran</span>
            </div>
        </td>
        <td style="text-align: right; vertical-align: top; width: 180px;">
            <div style="font-size: 9px; color: #64748b;">Tanggal Cetak:</div>
            <div style="font-size: 10px; font-weight: bold; color: #1e293b;">{{ now()->translatedFormat('d F Y') }}</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Pukul {{ now()->format('H:i') }} WIB</div>
        </td>
    </tr>
</table>

<div class="summary-box">
    <strong>Catatan Audit:</strong> Dokumen ini merangkum seluruh slot jam pelajaran (JP) aktif yang terjadwal namun tidak terisi agendanya atau belum diabsenkan oleh bapak/ibu guru pengajar pada rentang waktu di atas.
</div>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;">No</th>
            <th style="width: 14%;">Tanggal & Hari</th>
            <th style="width: 10%;">Jam KBM</th>
            <th class="th-left" style="width: 26%;">Nama Guru</th>
            <th style="width: 12%;">Kelas</th>
            <th class="th-left" style="width: 20%;">Mata Pelajaran</th>
            <th style="width: 14%;">Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($missedItems as $i => $item)
        <tr>
            <td style="color:#64748b;">{{ $i + 1 }}</td>
            <td>
                <strong>{{ $item['tanggal_formatted'] }}</strong><br>
                <span style="color:#64748b; font-size:8px;">{{ $item['hari'] }}</span>
            </td>
            <td>{{ $item['jam_rentang'] }}</td>
            <td class="td-left" style="font-weight:600; color:#0f172a;">
                {{ $item['guru_nama'] }}<br>
                <span style="font-size:8px; color:#64748b;">NIP. {{ $item['guru_nip'] }}</span>
            </td>
            <td style="font-weight:600; color:#1e40af;">{{ $item['kelas_nama'] }}</td>
            <td class="td-left">{{ $item['mapel_nama'] }}</td>
            <td>
                <span class="badge-unfilled">
                    {{ $item['status_penyebab'] === 'Belum Ada Agenda Sama Sekali' ? 'Tanpa Agenda' : 'Belum Absen' }}
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center; color:#94a3b8; padding:20px;">
                Tidak ada jadwal KBM yang terlewat pada periode ini. Semua sesi belajar terisi lengkap.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- Footer Signature Block -->
<div class="footer-table">
    <div class="sig-box">
        <div>{{ \App\Models\Setting::get('school_city', 'Ciamis') }}, {{ now()->translatedFormat('d F Y') }}</div>
        <div style="margin-top: 3px; font-weight: 600;">Kepala Sekolah,</div>
        <div class="sig-space"></div>
        <div class="sig-name">{{ \App\Models\Setting::get('headmaster_name', 'Drs. H. Kepala Sekolah, M.Pd.') }}</div>
        <div style="font-size: 8.5px; color: #64748b;">NIP. {{ \App\Models\Setting::get('headmaster_nip', '19700101 199512 1 001') }}</div>
    </div>
</div>

<div class="doc-footer">
    Dokumen Pengawasan Mutu KBM dicetak otomatis melalui Sistem Agenda & Presensi Mengajar Guru (SOPAN) | {{ $schoolName }}
</div>

</body>
</html>
