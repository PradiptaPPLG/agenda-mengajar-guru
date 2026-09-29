<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Data Guru - {{ $schoolName }}</title>
<style>
    @page {
        margin: 20px 25px;
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
        background-color: #059669;
        margin-bottom: 12px;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 10px;
    }
    .school-title {
        font-size: 15px;
        font-weight: bold;
        color: #047857;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .doc-title {
        font-size: 13px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 3px;
    }
    .meta-info {
        font-size: 9.5px;
        color: #475569;
        margin-top: 5px;
    }
    .meta-badge {
        display: inline-block;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        border-radius: 4px;
        padding: 2px 6px;
        font-size: 9px;
        font-weight: 600;
        color: #047857;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .data-table th {
        background-color: #0f172a;
        color: #ffffff;
        text-align: left;
        padding: 7px 8px;
        font-weight: bold;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        border: 1px solid #0f172a;
    }
    .data-table td {
        padding: 6px 8px;
        border: 1px solid #e2e8f0;
        font-size: 9px;
        vertical-align: top;
    }
    .data-table tr:nth-child(even) td { 
        background-color: #f8fafc; 
    }

    .badge {
        display: inline-block;
        padding: 1.5px 5px;
        border-radius: 3px;
        font-size: 8.5px;
        font-weight: 600;
        margin-bottom: 2px;
        margin-right: 2px;
    }
    .badge-role {
        background-color: #fef3c7;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .badge-tugas {
        background-color: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }
    .badge-mapel {
        background-color: #f1f5f9;
        color: #334155;
        border: 1px solid #cbd5e1;
    }
    .badge-kelas {
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .footer-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        page-break-inside: avoid;
    }
    .sig-box {
        width: 220px;
        float: right;
        text-align: center;
        font-size: 9.5px;
        color: #334155;
    }
    .sig-space {
        height: 40px;
    }
    .sig-name {
        font-weight: bold;
        text-decoration: underline;
        color: #0f172a;
    }

    .doc-footer {
        clear: both;
        padding-top: 10px;
        margin-top: 25px;
        border-top: 1px solid #e2e8f0;
        font-size: 8px;
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
            <div class="doc-title">Data Guru & Tenaga Pendidik</div>
            <div class="meta-info">
                <strong>Filter:</strong> <span class="meta-badge">{{ $filterText }}</span>
                <span style="margin-left: 10px; color: #64748b;">Total: <strong>{{ $users->count() }} Orang</strong></span>
            </div>
        </td>
        <td style="text-align: right; vertical-align: top; width: 200px;">
            <div style="font-size: 8.5px; color: #64748b;">Tanggal Cetak:</div>
            <div style="font-size: 10px; font-weight: bold; color: #1e293b;">{{ now()->translatedFormat('d F Y') }}</div>
            <div style="font-size: 8.5px; color: #64748b; margin-top: 2px;">Pukul {{ now()->format('H:i') }} WIB</div>
        </td>
    </tr>
</table>

<table class="data-table">
    <thead>
        <tr>
            <th style="width: 4%; text-align: center;">No</th>
            <th style="width: 16%;">NIP</th>
            <th style="width: 24%;">Nama Lengkap</th>
            <th style="width: 20%;">Role & Tugas Tambahan</th>
            <th style="width: 18%;">Mata Pelajaran</th>
            <th style="width: 18%;">Mengajar Kelas</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $idx => $u)
        @php
            $mapelList = $u->mapels->pluck('nama')
                ->merge($u->jadwalPelajarans->pluck('mataPelajaran.nama'))
                ->filter()
                ->unique()
                ->values();

            $kelasMengajarList = $u->jadwalPelajarans->pluck('kelas.nama')
                ->filter()
                ->unique()
                ->values();

            $kaprogJurusan = $u->guruProfile?->kaprog_jurusan;
            $nip = $u->guruProfile?->nip;
        @endphp
        <tr>
            <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
            <td style="font-family: monospace; font-weight: 500;">
                {{ $nip ? $nip : '-' }}
            </td>
            <td>
                <strong style="color: #0f172a; font-size: 9.5px;">{{ $u->name }}</strong>
                @if($u->email)
                    <div style="font-size: 8px; color: #64748b; margin-top: 1px;">{{ $u->email }}</div>
                @endif
            </td>
            <td>
                <span class="badge badge-role">{{ Str::title(str_replace('_', ' ', $u->role)) }}</span>
                @foreach($u->roles as $r)
                    <span class="badge badge-tugas">
                        {{ $r->name }}
                        @if(str_contains(strtolower($r->name), 'kaprog') && $kaprogJurusan)
                            ({{ $kaprogJurusan }})
                        @endif
                    </span>
                @endforeach
            </td>
            <td>
                @forelse($mapelList as $m)
                    <span class="badge badge-mapel">{{ $m }}</span>
                @empty
                    <span style="color: #94a3b8; font-style: italic; font-size: 8.5px;">-</span>
                @endforelse
            </td>
            <td>
                @forelse($kelasMengajarList as $k)
                    <span class="badge badge-kelas">{{ $k }}</span>
                @empty
                    <span style="color: #94a3b8; font-style: italic; font-size: 8.5px;">-</span>
                @endforelse
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">Tidak ada data guru yang sesuai filter.</td>
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
