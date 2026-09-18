<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Kehadiran Siswa</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #0f172a; margin: 0; padding: 20px; }
    h1 { font-size: 16px; font-weight: bold; margin: 0 0 4px; }
    .subtitle { color: #64748b; font-size: 11px; margin: 0 0 16px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f1f5f9; text-align: left; padding: 6px 10px; font-weight: 600; font-size: 10px; color: #475569; border: 1px solid #e2e8f0; }
    td { padding: 6px 10px; border: 1px solid #e2e8f0; text-align: center; }
    td:first-child { text-align: left; }
    tr:nth-child(even) td { background: #f8fafc; }
    .header-section { margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
    .school-name { font-size: 18px; font-weight: bold; color: #065f46; }
    .pct-good { color: #065f46; font-weight: bold; }
    .pct-warn { color: #92400e; font-weight: bold; }
    .pct-bad { color: #991b1b; font-weight: bold; }
</style>
</head>
<body>
<div class="header-section">
    <div class="school-name">{{ $schoolName }}</div>
    <h1>Laporan Kehadiran Siswa</h1>
    <div class="subtitle">
        Periode: {{ $startDate->format('d F Y') }} – {{ $endDate->format('d F Y') }}
        @if($selectedKelas) | Kelas: {{ $selectedKelas->nama }} @endif
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Siswa</th>
            <th style="text-align:center; color:#065f46;">Hadir</th>
            <th style="text-align:center; color:#92400e;">Sakit</th>
            <th style="text-align:center; color:#0369a1;">Izin</th>
            <th style="text-align:center; color:#991b1b;">Alpa</th>
            <th style="text-align:center; color:#6d28d9;">Disp.</th>
            <th style="text-align:center;">Total</th>
            <th style="text-align:center;">% Hadir</th>
        </tr>
    </thead>
    <tbody>
        @forelse($summary as $i => $item)
        @php $pct = $item['total'] > 0 ? round($item['hadir'] / $item['total'] * 100) : 0; @endphp
        <tr>
            <td style="color:#64748b; text-align:center;">{{ $i + 1 }}</td>
            <td style="text-align:left; font-weight:600;">{{ $item['siswa']->name }}</td>
            <td style="color:#065f46; font-weight:600;">{{ $item['hadir'] }}</td>
            <td style="color:#92400e; font-weight:600;">{{ $item['sakit'] }}</td>
            <td style="color:#0369a1; font-weight:600;">{{ $item['izin'] }}</td>
            <td style="color:#991b1b; font-weight:600;">{{ $item['alpa'] }}</td>
            <td style="color:#6d28d9; font-weight:600;">{{ $item['dispensasi'] }}</td>
            <td>{{ $item['total'] }}</td>
            <td class="{{ $pct >= 75 ? 'pct-good' : ($pct >= 50 ? 'pct-warn' : 'pct-bad') }}">{{ $pct }}%</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:16px;">Tidak ada data</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:24px; border-top:1px solid #e2e8f0; padding-top:8px; color:#94a3b8; font-size:9px;">
    Dicetak pada: {{ now()->format('d F Y H:i') }} WIB &nbsp;|&nbsp; {{ $schoolName }}
</div>
</body>
</html>
