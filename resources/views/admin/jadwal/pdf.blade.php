<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Jadwal Pelajaran - {{ $schoolName }}</title>
<style>
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #0f172a; margin: 0; padding: 20px; }
    .header-section { margin-bottom: 20px; border-bottom: 2px solid #0284c7; padding-bottom: 12px; }
    .school-name { font-size: 18px; font-weight: bold; color: #0369a1; }
    h1 { font-size: 15px; font-weight: bold; margin: 4px 0 2px; }
    .subtitle { color: #64748b; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th { background: #f8fafc; text-align: left; padding: 7px 10px; font-weight: bold; font-size: 10px; color: #334155; border: 1px solid #cbd5e1; }
    td { padding: 7px 10px; border: 1px solid #cbd5e1; vertical-align: top; font-size: 10px; }
    tr:nth-child(even) td { background: #f8fafc; }
    .day-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-weight: bold; background: #e0f2fe; color: #0369a1; }
    .footer { margin-top: 25px; text-align: right; font-size: 10px; color: #64748b; }
</style>
</head>
<body>
<div class="header-section">
    <div class="school-name">{{ $schoolName }}</div>
    <h1>Jadwal Pelajaran</h1>
    <div class="subtitle">
        Tahun Ajaran: {{ $schoolYear }} | Dicetak pada: {{ now()->translatedFormat('d F Y, H:i') }} WIB
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 5%;">No</th>
            <th style="width: 15%;">Hari</th>
            <th style="width: 15%;">Jam Pelajaran</th>
            <th style="width: 15%;">Kelas</th>
            <th style="width: 25%;">Mata Pelajaran</th>
            <th style="width: 25%;">Guru Pengampu</th>
        </tr>
    </thead>
    <tbody>
        @forelse($jadwals as $idx => $j)
        <tr>
            <td style="text-align: center;">{{ $idx + 1 }}</td>
            <td>
                <span class="day-badge">{{ $hariNames[$j->hari] ?? 'Hari '.$j->hari }}</span>
            </td>
            <td>{{ substr($j->jam_mulai, 0, 5) }} – {{ substr($j->jam_selesai, 0, 5) }}</td>
            <td><strong>{{ $j->kelas->nama ?? '-' }}</strong></td>
            <td>{{ $j->mataPelajaran->nama ?? '-' }} ({{ $j->mataPelajaran->kode ?? '-' }})</td>
            <td>{{ $j->guru->name ?? '-' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada jadwal pelajaran</td>
        </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    Dokumen Resmi Agenda Mengajar Sekolah
</div>
</body>
</html>
