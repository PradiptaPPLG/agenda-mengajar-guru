<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kehadiran Guru</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #0f172a; margin: 0; padding: 20px; }
    h1 { font-size: 16px; font-weight: bold; margin: 0 0 4px; }
    .subtitle { color: #64748b; font-size: 11px; margin: 0 0 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background: #f1f5f9; text-align: left; padding: 6px 10px; font-weight: 600; font-size: 10px; color: #475569; border: 1px solid #e2e8f0; }
    td { padding: 6px 10px; border: 1px solid #e2e8f0; vertical-align: top; }
    tr:nth-child(even) td { background: #f8fafc; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 9px; font-weight: 600; }
    .badge-hadir { background: #d1fae5; color: #065f46; }
    .badge-sakit { background: #fef3c7; color: #92400e; }
    .badge-alpa { background: #fee2e2; color: #991b1b; }
    .summary-table td { text-align: center; }
    .summary-table td:first-child { text-align: left; }
    .header-section { margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .school-name { font-size: 18px; font-weight: bold; color: #1e40af; }
    .pct-good { color: #065f46; font-weight: bold; }
    .pct-warn { color: #92400e; font-weight: bold; }
    .pct-bad { color: #991b1b; font-weight: bold; }
</style>
</head>
<body>
<div class="header-section">
    <div class="school-name">{{ $schoolName }}</div>
    <h1>Laporan Kehadiran Guru</h1>
    <div class="subtitle">
        Periode: {{ $startDate->format('d F Y') }} – {{ $endDate->format('d F Y') }}
        @if($selectedGuru) | Guru: {{ $selectedGuru->name }} @endif
        @if($selectedKelas) | Kelas: {{ $selectedKelas->nama }} @endif
    </div>
</div>

{{-- Summary --}}
@if($summary->count() > 0)
<h2 style="font-size:13px; margin:0 0 8px; font-weight:600;">Ringkasan Per Guru</h2>
<table class="summary-table" style="margin-bottom:24px;">
    <thead>
        <tr>
            <th>Nama Guru</th>
            <th style="text-align:center;">Hadir</th>
            <th style="text-align:center;">Sakit</th>
            <th style="text-align:center;">Alpa</th>
            <th style="text-align:center;">Total</th>
            <th style="text-align:center;">% Hadir</th>
        </tr>
    </thead>
    <tbody>
        @foreach($summary as $item)
        @php $pct = $item['total'] > 0 ? round($item['hadir'] / $item['total'] * 100) : 0; @endphp
        <tr>
            <td style="font-weight:600;">{{ $item['guru']->name }}</td>
            <td style="text-align:center; color:#065f46; font-weight:600;">{{ $item['hadir'] }}</td>
            <td style="text-align:center; color:#92400e; font-weight:600;">{{ $item['sakit'] }}</td>
            <td style="text-align:center; color:#991b1b; font-weight:600;">{{ $item['alpa'] }}</td>
            <td style="text-align:center;">{{ $item['total'] }}</td>
            <td style="text-align:center;" class="{{ $pct >= 80 ? 'pct-good' : ($pct >= 60 ? 'pct-warn' : 'pct-bad') }}">{{ $pct }}%</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Detail --}}
<h2 style="font-size:13px; margin:0 0 8px; font-weight:600;">Detail Kehadiran</h2>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Guru</th>
            <th>Mata Pelajaran</th>
            <th>Kelas</th>
            <th>Status</th>
            <th>Keterangan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($kehadiran as $i => $kh)
        <tr>
            <td style="text-align:center; color:#64748b;">{{ $i + 1 }}</td>
            <td>{{ $kh->pertemuan?->tanggal?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-weight:600;">{{ $kh->guru->name }}</td>
            <td>{{ $kh->pertemuan?->jadwal?->mataPelajaran?->nama ?? '—' }}</td>
            <td>{{ $kh->pertemuan?->jadwal?->kelas?->nama ?? '—' }}</td>
            <td>
                <span class="badge badge-{{ $kh->status }}">{{ $kh->status_label }}</span>
                @if($kh->jenis_alpa)<br><small style="color:#64748b;">{{ $kh->jenis_alpa_label }}</small>@endif
            </td>
            <td style="color:#64748b; font-size:10px;">
                {{ $kh->guru_pengganti_nama ? 'Pengganti: '.$kh->guru_pengganti_nama : ($kh->keterangan ?? '—') }}
            </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center; color:#94a3b8; padding:16px;">Tidak ada data</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:24px; border-top:1px solid #e2e8f0; padding-top:8px; color:#94a3b8; font-size:9px;">
    Dicetak pada: {{ now()->format('d F Y H:i') }} WIB &nbsp;|&nbsp; {{ $schoolName }}
</div>
</body>
</html>
