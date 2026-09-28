<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Kehadiran - {{ $kelas->nama }} - {{ $mataPelajaran->nama }}</title>
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
        background-color: #2563eb;
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
        color: #1d4ed8;
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
        line-height: 1.5;
    }
    .meta-badge {
        display: inline-block;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: 600;
        color: #1d4ed8;
    }
    .kpi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 6px 0;
        margin-bottom: 16px;
    }
    .kpi-card {
        padding: 8px 10px;
        border-radius: 6px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }
    .kpi-card-hadir { background-color: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .kpi-card-terlambat { background-color: #fffbeb; border-color: #fde68a; color: #b45309; }
    .kpi-card-sakit { background-color: #fff7ed; border-color: #fed7aa; color: #9a3412; }
    .kpi-card-izin { background-color: #f0f9ff; border-color: #bae6fd; color: #075985; }
    .kpi-card-dispensasi { background-color: #faf5ff; border-color: #e9d5ff; color: #6b21a8; }
    .kpi-card-alpa { background-color: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .kpi-card-total { background-color: #f8fafc; border-color: #e2e8f0; color: #334155; }
    .kpi-num { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
    .kpi-label { font-size: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.3px; }
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .data-table th {
        background-color: #1e293b;
        color: #ffffff;
        text-align: center;
        padding: 6px 6px;
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border: 1px solid #1e293b;
    }
    .data-table th.th-left { text-align: left; }
    .data-table td {
        padding: 5px 6px;
        border: 1px solid #e2e8f0;
        text-align: center;
        font-size: 9px;
    }
    .data-table td.td-left { text-align: left; }
    .data-table tr:nth-child(even) td { background-color: #f8fafc; }
    .pct-badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 8.5px;
    }
    .pct-good { background-color: #dcfce7; color: #15803d; }
    .pct-warn { background-color: #fef9c3; color: #a16207; }
    .pct-bad { background-color: #fee2e2; color: #b91c1c; }
    .sig-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
        page-break-inside: avoid;
    }
    .sig-box {
        text-align: center;
        font-size: 10px;
        color: #334155;
    }
    .sig-space {
        height: 50px;
    }
    .sig-name {
        font-weight: bold;
        text-decoration: underline;
        color: #0f172a;
    }
</style>
</head>
<body>

<div class="top-bar"></div>

<table class="header-table">
    <tr>
        <td>
            <div class="school-title">{{ $schoolName }}</div>
            <div class="doc-title">Rekapitulasi Kehadiran Siswa Per Mata Pelajaran</div>
            <div class="meta-info">
                <strong>Guru Pengampu:</strong> {{ $guru->name }} &nbsp;|&nbsp;
                <strong>Mata Pelajaran:</strong> <span class="meta-badge">{{ $mataPelajaran->nama }}</span> &nbsp;|&nbsp;
                <strong>Kelas:</strong> <span class="meta-badge">{{ $kelas->nama }}</span><br>
                <strong>Periode:</strong> 
                @if($periodeType === 'bulanan')
                    <span class="meta-badge">{{ \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}</span>
                @else
                    <span class="meta-badge">{{ $semesterLabel }} - TA {{ $tahunAjaran }}</span>
                @endif
                &nbsp;|&nbsp; <strong>Total Sesi KBM:</strong> {{ $totalPertemuanKelas }} Pertemuan
            </div>
        </td>
        <td style="text-align: right; vertical-align: top; width: 180px;">
            <div style="font-size: 9px; color: #64748b;">Tanggal Unduh:</div>
            <div style="font-size: 10px; font-weight: bold; color: #1e293b;">{{ now()->translatedFormat('d F Y') }}</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Pukul {{ now()->format('H:i') }} WIB</div>
        </td>
    </tr>
</table>

<!-- KPI Summary Table -->
<table class="kpi-table">
    <tr>
        <td class="kpi-card kpi-card-total">
            <div class="kpi-num">{{ count($rekapSiswa) }}</div>
            <div class="kpi-label">Total Siswa</div>
        </td>
        <td class="kpi-card kpi-card-hadir">
            <div class="kpi-num">{{ number_format($totalHadir) }}</div>
            <div class="kpi-label">Hadir</div>
        </td>
        <td class="kpi-card kpi-card-terlambat">
            <div class="kpi-num">{{ number_format($totalTerlambat) }}</div>
            <div class="kpi-label">Terlambat</div>
        </td>
        <td class="kpi-card kpi-card-sakit">
            <div class="kpi-num">{{ number_format($totalSakit) }}</div>
            <div class="kpi-label">Sakit</div>
        </td>
        <td class="kpi-card kpi-card-izin">
            <div class="kpi-num">{{ number_format($totalIzin) }}</div>
            <div class="kpi-label">Izin</div>
        </td>
        <td class="kpi-card kpi-card-dispensasi">
            <div class="kpi-num">{{ number_format($totalDispensasi) }}</div>
            <div class="kpi-label">Dispensasi</div>
        </td>
        <td class="kpi-card kpi-card-alpa">
            <div class="kpi-num">{{ number_format($totalAlpa) }}</div>
            <div class="kpi-label">Alpa</div>
        </td>
    </tr>
</table>

<!-- Main Data Table -->
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;">No</th>
            <th class="th-left" style="width: 27%;">Nama Siswa</th>
            <th style="width: 11%;">NIS</th>
            <th style="width: 7%; color: #86efac;">Hadir</th>
            <th style="width: 7%; color: #fde68a;">Terlambat</th>
            <th style="width: 7%; color: #fed7aa;">Sakit</th>
            <th style="width: 7%; color: #bae6fd;">Izin</th>
            <th style="width: 7%; color: #ddd6fe;">Disp.</th>
            <th style="width: 7%; color: #fca5a5;">Alpa</th>
            <th style="width: 7%;">Total</th>
            <th style="width: 9%;">% Kehadiran</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rekapSiswa as $i => $row)
        <tr>
            <td style="color:#64748b;">{{ $i + 1 }}</td>
            <td class="td-left" style="font-weight:600; color:#0f172a;">{{ $row['siswa']->name }}</td>
            <td style="color:#64748b;">{{ $row['nis'] }}</td>
            <td style="color:#166534; font-weight:600;">{{ $row['hadir'] }}</td>
            <td style="color:#b45309; font-weight:600;">{{ $row['terlambat'] }}</td>
            <td style="color:#9a3412; font-weight:600;">{{ $row['sakit'] }}</td>
            <td style="color:#0369a1; font-weight:600;">{{ $row['izin'] }}</td>
            <td style="color:#6d28d9; font-weight:600;">{{ $row['dispensasi'] }}</td>
            <td style="color:#991b1b; font-weight:600;">{{ $row['alpa'] }}</td>
            <td style="font-weight:bold; color:#334155;">{{ $row['total'] }}</td>
            <td>
                <span class="pct-badge {{ $row['persentase'] >= 85 ? 'pct-good' : ($row['persentase'] >= 70 ? 'pct-warn' : 'pct-bad') }}">
                    {{ $row['persentase'] }}%
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="11" style="padding: 16px; color:#64748b;">Belum ada catatan presensi siswa untuk kelas dan mata pelajaran ini pada periode terpilih.</td>
        </tr>
        @endforelse
    </tbody>
</table>

<!-- Signatures -->
<table class="sig-table">
    <tr>
        <td style="width: 50%;"></td>
        <td style="width: 50%;">
            <div class="sig-box">
                <div>Guru Pengampu,</div>
                <div class="sig-space"></div>
                <div class="sig-name">{{ $guru->name }}</div>
                @if($guru->nip)
                    <div style="font-size: 8.5px; color:#64748b;">NIP. {{ $guru->nip }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>

</body>
</html>
