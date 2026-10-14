<x-layouts.admin title="Pusat Pemulihan Data (Trash)">
    <div class="space-y-6">
        <!-- Top Header & Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-rose-600 uppercase tracking-wider mb-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Pusat Pemulihan & Integritas Data</span>
                </div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manajemen Keranjang Sampah (Trash)</h1>
                <p class="text-sm text-slate-500 mt-0.5">Kelola data master yang dihapus oleh admin atau guru. Anda dapat memulihkannya kembali atau menghapusnya secara permanen.</p>
            </div>
            
            @if ($items->total() > 0)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('super-admin.trash.restore-all', ['type' => $type]) }}" onsubmit="return confirm('Pulihkan SEMUA data {{ strtoupper($type) }} di keranjang sampah?');">
                        @csrf
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold rounded-xl shadow-xs transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <span>Pulihkan Semua ({{ $items->total() }})</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('super-admin.trash.empty', ['type' => $type]) }}" onsubmit="return confirm('PERINGATAN: Kosongkan keranjang sampah {{ strtoupper($type) }}? Data akan terhapus PERMANEN dan tidak dapat dikembalikan!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-semibold rounded-xl transition">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Kosongkan Sampah</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- Alert Notification -->
        @if (session('success'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-xs font-semibold">Tutup</button>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Tutup</button>
            </div>
        @endif

        <!-- Tab Navigation Bar -->
        <div class="border-b border-slate-200 bg-white px-3 pt-2 rounded-2xl border shadow-xs overflow-x-auto">
            <nav class="flex gap-2 min-w-max">
                @php
                    $tabs = [
                        ['id' => 'guru', 'label' => 'Data Guru', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                        ['id' => 'siswa', 'label' => 'Data Siswa', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['id' => 'kelas', 'label' => 'Kelas & Rombel', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['id' => 'jadwal', 'label' => 'Jadwal Pelajaran', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['id' => 'mapel', 'label' => 'Mata Pelajaran', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['id' => 'agenda', 'label' => 'Agenda & Absensi', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                    ];
                @endphp

                @foreach ($tabs as $tab)
                    @php
                        $isActive = $type === $tab['id'];
                        $count = $counts[$tab['id']] ?? 0;
                    @endphp
                    <a href="{{ route('super-admin.trash.index', ['type' => $tab['id']]) }}" class="flex items-center gap-2 py-3 px-4 text-xs font-semibold border-b-2 transition {{ $isActive ? 'border-sky-600 text-sky-700 bg-sky-50/50 rounded-t-xl' : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-slate-300' }}">
                        <svg class="w-4 h-4 {{ $isActive ? 'text-sky-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                        </svg>
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $count > 0 ? ($isActive ? 'bg-sky-600 text-white' : 'bg-slate-200 text-slate-700') : 'bg-slate-100 text-slate-400' }}">
                            {{ $count }}
                        </span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Filter Search Bar -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
            <form method="GET" action="{{ route('super-admin.trash.index') }}" class="w-full sm:w-80 flex items-center gap-2">
                <input type="hidden" name="type" value="{{ $type }}">
                <div class="relative w-full">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari data terhapus..." class="w-full pl-9 pr-3 py-2 text-xs rounded-xl border border-slate-200 focus:outline-hidden focus:ring-2 focus:ring-sky-500/20 focus:border-sky-500 transition">
                </div>
                <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-800 hover:bg-slate-900 text-white rounded-xl transition">
                    Cari
                </button>
                @if ($search)
                    <a href="{{ route('super-admin.trash.index', ['type' => $type]) }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 rounded-xl transition">
                        Reset
                    </a>
                @endif
            </form>

            <span class="text-xs text-slate-500 font-medium">
                Menampilkan <strong>{{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }}</strong> dari total <strong>{{ $items->total() }}</strong> entri
            </span>
        </div>

        <!-- Table Data -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4">Nama / Identitas</th>
                            <th class="py-3 px-4">Detail Informasi</th>
                            <th class="py-3 px-4">Waktu Dihapus</th>
                            <th class="py-3 px-4 text-right">Tindakan Pemulihan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($items as $item)
                            <tr class="hover:bg-slate-50/75 transition">
                                <!-- Kolom Identitas -->
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    @if ($type === 'guru' || $type === 'siswa')
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                                {{ substr($item->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-900 block">{{ $item->name }}</span>
                                                <span class="text-xs text-slate-400">{{ $item->email }}</span>
                                            </div>
                                        </div>
                                    @elseif ($type === 'kelas')
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                                {{ $item->tingkat }}
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-900 block">{{ $item->nama }}</span>
                                                <span class="text-xs text-slate-400">Tahun: {{ $item->tahun_ajaran ?? '-' }}</span>
                                            </div>
                                        </div>
                                    @elseif ($type === 'jadwal')
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ $item->mataPelajaran?->nama ?? 'Mapel Tidak Diketahui' }}</span>
                                            <span class="text-xs text-slate-500">{{ $item->kelas?->nama ?? 'Kelas -' }} &bull; Guru: {{ $item->guru?->name ?? '-' }}</span>
                                        </div>
                                    @elseif ($type === 'mapel')
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ $item->nama }}</span>
                                            <span class="text-xs text-slate-400 font-mono">{{ $item->kode ?? '-' }} &bull; {{ $item->jenis ?? 'Umum' }}</span>
                                        </div>
                                    @elseif ($type === 'agenda')
                                        <div>
                                            <span class="font-bold text-slate-900 block truncate max-w-sm">{{ $item->materi_ajar ?? 'Agenda Mengajar' }}</span>
                                            <span class="text-xs text-slate-500">
                                                Tgl: {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }} &bull; {{ $item->jadwal?->kelas?->nama ?? '-' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Kolom Detail -->
                                <td class="py-3.5 px-4 text-xs text-slate-600">
                                    @if ($type === 'guru')
                                        <span>NIP: <strong class="font-mono">{{ $item->guruProfile?->nip ?? '-' }}</strong></span>
                                    @elseif ($type === 'siswa')
                                        <span>NIS: <strong class="font-mono">{{ $item->siswaProfile?->nis ?? '-' }}</strong> &bull; Kelas: {{ $item->siswaProfile?->kelas?->nama ?? '-' }}</span>
                                    @elseif ($type === 'kelas')
                                        <span>Wali Kelas: {{ $item->waliKelas?->name ?? '-' }}</span>
                                    @elseif ($type === 'jadwal')
                                        <span>Hari {{ $item->hari }} &bull; {{ substr($item->jam_mulai, 0, 5) }} - {{ substr($item->jam_selesai, 0, 5) }}</span>
                                    @elseif ($type === 'mapel')
                                        <span>Kelompok Blok: {{ $item->kelompok_blok ?? '-' }}</span>
                                    @elseif ($type === 'agenda')
                                        <span class="text-slate-500">Guru: {{ $item->jadwal?->guru?->name ?? '-' }}</span>
                                    @endif
                                </td>

                                <!-- Kolom Deleted At -->
                                <td class="py-3.5 px-4 text-xs text-slate-500">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>{{ $item->deleted_at->translatedFormat('d M Y H:i') }}</span>
                                    </div>
                                    <span class="text-[11px] text-slate-400 block mt-0.5">({{ $item->deleted_at->diffForHumans() }})</span>
                                </td>

                                <!-- Kolom Aksi -->
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Tombol Restore -->
                                        <form method="POST" action="{{ route('super-admin.trash.restore', ['type' => $type, 'id' => $item->id]) }}" onsubmit="return confirm('Pulihkan data ini kembali ke sistem aktif?');">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition" title="Pulihkan">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                <span>Pulihkan</span>
                                            </button>
                                        </form>

                                        <!-- Tombol Force Delete -->
                                        <form method="POST" action="{{ route('super-admin.trash.force-delete', ['type' => $type, 'id' => $item->id]) }}" onsubmit="return confirm('Hapus PERMANEN? Data yang dihapus tidak dapat dipulihkan lagi!');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 text-xs font-semibold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition" title="Hapus Permanen">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-slate-400 text-sm">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </div>
                                        <p class="font-medium text-slate-600">Keranjang sampah {{ strtoupper($type) }} kosong.</p>
                                        <p class="text-xs text-slate-400">Tidak ada data yang dihapus sementara saat ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($items->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $items->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
