<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kehadiran Siswa - {{ $schoolName }}</title>
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
    
    /* Top Brand Bar */
    .top-bar {
        height: 5px;
        background-color: #0d9488;
        margin-bottom: 15px;
    }

    /* Header Section */
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
        color: #0f766e;
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

    /* KPI Summary Box Grid */
    .kpi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px 0;
        margin-bottom: 18px;
    }
    .kpi-card {
        padding: 8px 10px;
        border-radius: 6px;
        text-align: center;
        border: 1px solid #e2e8f0;
    }
    .kpi-card-hadir { background-color: #f0fdf4; border-color: #bbf7d0; color: #166534; }
    .kpi-card-sakit { background-color: #fffbeb; border-color: #fef08a; color: #854d0e; }
    .kpi-card-izin { background-color: #f0f9ff; border-color: #bae6fd; color: #075985; }
    .kpi-card-alpa { background-color: #fef2f2; border-color: #fecaca; color: #991b1b; }
    .kpi-card-total { background-color: #f8fafc; border-color: #e2e8f0; color: #334155; }

    .kpi-num { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
    .kpi-label { font-size: 8px; text-transform: uppercase; font-weight: 600; letter-spacing: 0.3px; }

    /* Data Table */
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
        font-size: 9.5px;
    }
    .data-table td.td-left { text-align: left; }
    .data-table tr:nth-child(even) td { background-color: #f8fafc; }

    /* Badges & Percentages */
    .pct-badge {
        display: inline-block;
        padding: 2px 6px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 9px;
    }
    .pct-good { background-color: #dcfce7; color: #15803d; }
    .pct-warn { background-color: #fef9c3; color: #a16207; }
    .pct-bad { background-color: #fee2e2; color: #b91c1c; }

    /* Footer & Signature Section */
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
            <div class="doc-title">Laporan Rekapitulasi Kehadiran Siswa</div>
            <div class="meta-info">
                <strong>Tahun Ajaran:</strong> <span class="meta-badge">{{ $tahunAjaran !== 'all' ? $tahunAjaran : 'Semua Tahun' }}</span> &nbsp;|&nbsp;
                <strong>Semester:</strong> <span class="meta-badge">{{ $semesterLabel }}</span> &nbsp;|&nbsp;
                <strong>Periode:</strong> {{ $startDate->format('d M Y') }} – {{ $endDate->format('d M Y') }}
                @if($selectedKelas)
                &nbsp;|&nbsp; <strong>Kelas:</strong> <span class="meta-badge" style="background:#ccfbf1; color:#0f766e; border-color:#99f6e4;">{{ $selectedKelas->nama }}</span>
                @endif
            </div>
        </td>
        <td style="text-align: right; vertical-align: top; width: 180px;">
            <div style="font-size: 9px; color: #64748b;">Tanggal Cetak:</div>
            <div style="font-size: 10px; font-weight: bold; color: #1e293b;">{{ now()->translatedFormat('d F Y') }}</div>
            <div style="font-size: 9px; color: #64748b; margin-top: 2px;">Pukul {{ now()->format('H:i') }} WIB</div>
        </td>
    </tr>
</table>

@php
    $totalHadir = $summary->sum('hadir');
    $totalSakit = $summary->sum('sakit');
    $totalIzin = $summary->sum('izin');
    $totalAlpa = $summary->sum('alpa');
    $totalDisp = $summary->sum('dispensasi');
    $totalOverall = $summary->sum('total');
    $avgPct = $totalOverall > 0 ? round(($totalHadir / $totalOverall) * 100, 1) : 0;
@endphp

<!-- KPI Summary Table -->
<table class="kpi-table">
    <tr>
        <td class="kpi-card kpi-card-hadir">
            <div class="kpi-num">{{ number_format($totalHadir) }}</div>
            <div class="kpi-label">Total Hadir</div>
        </td>
        <td class="kpi-card kpi-card-sakit">
            <div class="kpi-num">{{ number_format($totalSakit) }}</div>
            <div class="kpi-label">Total Sakit</div>
        </td>
        <td class="kpi-card kpi-card-izin">
            <div class="kpi-num">{{ number_format($totalIzin + $totalDisp) }}</div>
            <div class="kpi-label">Izin / Disp.</div>
        </td>
        <td class="kpi-card kpi-card-alpa">
            <div class="kpi-num">{{ number_format($totalAlpa) }}</div>
            <div class="kpi-label">Total Alpa</div>
        </td>
        <td class="kpi-card kpi-card-total">
            <div class="kpi-num" style="color: #0f766e;">{{ $avgPct }}%</div>
            <div class="kpi-label">Rata-rata Hadir</div>
        </td>
    </tr>
</table>

<!-- Main Data Table -->
<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%;">No</th>
            <th class="th-left" style="width: 32%;">Nama Siswa</th>
            <th style="width: 9%; color: #86efac;">Hadir</th>
            <th style="width: 9%; color: #fef08a;">Sakit</th>
            <th style="width: 9%; color: #bae6fd;">Izin</th>
            <th style="width: 9%; color: #fca5a5;">Alpa</th>
            <th style="width: 9%; color: #ddd6fe;">Disp.</th>
            <th style="width: 9%;">Total</th>
            <th style="width: 10%;">% Hadir</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summary as $i => $item)
        @php 
            $pct = $item['total'] > 0 ? round($item['hadir'] / $item['total'] * 100) : 0; 
        @endphp
        <tr>
            <td style="color:#64748b;">{{ $i + 1 }}</td>
            <td class="td-left" style="font-weight:600; color:#0f172a;">{{ $item['siswa']->name }}</td>
            <td style="color:#166534; font-weight:600;">{{ $item['hadir'] }}</td>
            <td style="color:#854d0e; font-weight:600;">{{ $item['sakit'] }}</td>
            <td style="color:#0369a1; font-weight:600;">{{ $item['izin'] }}</td>
            <td style="color:#991b1b; font-weight:600;">{{ $item['alpa'] }}</td>
            <td style="color:#6d28d9; font-weight:600;">{{ $item['dispensasi'] }}</td>
            <td style="font-weight:bold; color:#334155;">{{ $item['total'] }}</td>
            <td>
                <span class="pct-badge {{ $pct >= 80 ? 'pct-good' : ($pct >= 65 ? 'pct-warn' : 'pct-bad') }}">
                    {{ $pct }}%
                </span>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center; color:#94a3b8; padding:20px;">
                Tidak ada data rekapitulasi kehadiran siswa pada periode ini.
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
    Dokumen ini dicetak secara otomatis melalui Sistem Informasi Agenda & Presensi Mengajar Guru (SOPAN) | {{ $schoolName }}
</div>

</body>
</html>
