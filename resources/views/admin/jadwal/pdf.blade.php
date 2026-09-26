<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Jadwal Pelajaran - {{ $schoolName }}</title>
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
        background-color: #0284c7;
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
        color: #0369a1;
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
    }
    .meta-badge {
        display: inline-block;
        background: #e0f2fe;
        border: 1px solid #bae6fd;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: 600;
        color: #0369a1;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .data-table th {
        background-color: #0f172a;
        color: #ffffff;
        text-align: center;
        padding: 8px;
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border: 1px solid #0f172a;
    }
    .data-table th.th-left { text-align: left; }
    .data-table td {
        padding: 7px 8px;
        border: 1px solid #e2e8f0;
        text-align: center;
        font-size: 9.5px;
        vertical-align: top;
    }
    .data-table td.td-left { text-align: left; }
    .data-table tr:nth-child(even) td { background-color: #f8fafc; }

    .day-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: bold;
        background: #e0f2fe;
        color: #0369a1;
        font-size: 9px;
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
            <div class="doc-title">Master Dokumen Jadwal Pelajaran</div>
            <div class="meta-info">
                <strong>Tahun Ajaran:</strong> <span class="meta-badge">{{ $schoolYear }}</span>
            </div>
        </td>
        <td style="text-align: right; vertical-align: top; width: 180px;">
            <div style="font-size: 9px; color: #64748b;">Tanggal Cetak:</div>
            <div style="font-size: 10px; font-weight: bold; color: #1e293b;">{{ now()->translatedFormat('d F Y') }}</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Pukul {{ now()->format('H:i') }} WIB</div>
        </td>
    </tr>
</table>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 12%;">Hari</th>
            <th style="width: 16%;">Jam Pelajaran</th>
            <th style="width: 12%;">Kelas</th>
            <th class="th-left" style="width: 28%;">Mata Pelajaran</th>
            <th class="th-left" style="width: 27%;">Guru Pengampu</th>
        </tr>
    </thead>
    <tbody>
        @forelse($jadwals as $idx => $j)
        <tr>
            <td style="color: #64748b;">{{ $idx + 1 }}</td>
            <td>
                <span class="day-badge">{{ $hariNames[$j->hari] ?? 'Hari '.$j->hari }}</span>
            </td>
            <td style="font-weight: 500;">{{ substr($j->jam_mulai, 0, 5) }} – {{ substr($j->jam_selesai, 0, 5) }}</td>
            <td><strong>{{ $j->kelas->nama ?? '-' }}</strong></td>
            <td class="td-left" style="font-weight: 600; color: #0f172a;">
                {{ $j->mataPelajaran->nama ?? '-' }}
                @if(!empty($j->mataPelajaran->kode))
                <span style="color: #64748b; font-weight: normal;">({{ $j->mataPelajaran->kode }})</span>
                @endif
            </td>
            <td class="td-left">{{ $j->guru->name ?? '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data jadwal pelajaran.</td>
        </tr>
        @endforelse
    </tbody>
</table>

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
    Dokumen Resmi Agenda Mengajar Sekolah (SOPAN) | {{ $schoolName }}
</div>

</body>
</html>
