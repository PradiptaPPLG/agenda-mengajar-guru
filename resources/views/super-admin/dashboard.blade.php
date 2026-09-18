<x-layouts.admin>
    <x-slot:title>Dashboard Super Admin</x-slot:title>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        @foreach([
            ['label' => 'Total Guru', 'value' => $stats['total_guru'], 'color' => 'blue', 'href' => route('admin.users.index', ['role' => 'guru'])],
            ['label' => 'Total Siswa', 'value' => $stats['total_siswa'], 'color' => 'emerald', 'href' => route('admin.users.index', ['role' => 'siswa'])],
            ['label' => 'Total Kelas', 'value' => $stats['total_kelas'], 'color' => 'violet', 'href' => route('admin.kelas.index')],
            ['label' => 'Total Admin', 'value' => $stats['total_admin'], 'color' => 'amber', 'href' => route('admin.users.index', ['role' => 'admin'])],
            ['label' => 'Pertemuan Hari Ini', 'value' => $stats['pertemuan_hari_ini'], 'color' => 'slate', 'href' => '#'],
        ] as $stat)
        <a href="{{ $stat['href'] }}" class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-sm hover:border-{{ $stat['color'] }}-300 transition-all">
            <p class="text-2xl font-bold text-{{ $stat['color'] }}-600">{{ $stat['value'] }}</p>
            <p class="text-sm text-slate-500 mt-1">{{ $stat['label'] }}</p>
        </a>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-4">Manajemen Data</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    ['href' => route('admin.users.index'), 'label' => 'Pengguna', 'color' => 'blue'],
                    ['href' => route('admin.kelas.index'), 'label' => 'Kelas', 'color' => 'emerald'],
                    ['href' => route('admin.jadwal.index'), 'label' => 'Jadwal', 'color' => 'violet'],
                    ['href' => route('admin.mata-pelajaran.index'), 'label' => 'Mata Pelajaran', 'color' => 'amber'],
                ] as $item)
                <a href="{{ $item['href'] }}" class="flex items-center justify-center py-3 bg-{{ $item['color'] }}-50 hover:bg-{{ $item['color'] }}-100 text-{{ $item['color'] }}-700 text-sm font-medium rounded-xl border border-{{ $item['color'] }}-200 transition-colors">
                    {{ $item['label'] }}
                </a>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="font-semibold text-slate-900 mb-4">Laporan & Pengaturan</h3>
            <div class="grid grid-cols-2 gap-3">
                @foreach([
                    ['href' => route('kepala-sekolah.report.guru'), 'label' => 'Laporan Guru', 'color' => 'red'],
                    ['href' => route('kepala-sekolah.report.siswa'), 'label' => 'Laporan Siswa', 'color' => 'orange'],
                    ['href' => route('super-admin.settings'), 'label' => 'Pengaturan', 'color' => 'slate'],
                ] as $item)
                <a href="{{ $item['href'] }}" class="flex items-center justify-center py-3 bg-{{ $item['color'] }}-50 hover:bg-{{ $item['color'] }}-100 text-{{ $item['color'] }}-700 text-sm font-medium rounded-xl border border-{{ $item['color'] }}-200 transition-colors">
                    {{ $item['label'] }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.admin>
