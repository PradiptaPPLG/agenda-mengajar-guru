<x-layouts.admin>
    <x-slot:title>Kalender Hari Libur & Agenda Sekolah</x-slot:title>

    {{-- Header Banner & Tombol Tindakan Cepat --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Kalender Hari Libur & Kalender Pendidikan
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola hari libur nasional, cuti bersama, serta libur jeda semester untuk membebaskan kewajiban presensi KBM secara otomatis.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            {{-- Tombol 1-Klik Sinkronisasi API --}}
            <form method="POST" action="{{ route('admin.hari-libur.sync') }}" onsubmit="return confirm('Sinkronkan seluruh data hari libur nasional resmi untuk tahun ' + (document.getElementById('sync-tahun-val').value) + ' dari API pemerintah?');" class="inline-flex">
                @csrf
                <input type="hidden" name="tahun" id="sync-tahun-val" value="{{ request('tahun') ?: now()->year }}">
                <button type="submit" class="inline-flex items-center gap-2 px-3.5 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-xl border border-indigo-200 transition-colors shadow-sm">
                    <svg class="w-4 h-4 text-indigo-600 animate-spin-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <span>1-Klik Sinkron Libur Nasional ({{ request('tahun') ?: now()->year }})</span>
                </button>
            </form>

            {{-- Tombol Buka Modal Rentang Libur --}}
            <button type="button" onclick="document.getElementById('modal-range-libur').classList.remove('hidden')" class="inline-flex items-center gap-2 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 text-xs font-semibold rounded-xl border border-amber-200 transition-colors shadow-sm">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>+ Rentang Libur (Multi-Hari)</span>
            </button>
        </div>
    </div>

    {{-- Form Tambah Hari Libur Satuan --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900 mb-2 flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Hari Libur Satuan (1 Hari)
        </h3>
        
        <form method="POST" action="{{ route('admin.hari-libur.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            @csrf
            <div class="sm:col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal <span class="text-rose-500">*</span></label>
                <input type="date" name="tanggal" value="{{ old('tanggal') }}" required
                       class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 @error('tanggal') border-rose-500 @enderror">
                @error('tanggal')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-4">
                <label class="block text-xs font-medium text-slate-600 mb-1">Keterangan Libur <span class="text-rose-500">*</span></label>
                <input type="text" name="keterangan" value="{{ old('keterangan') }}" required placeholder="Contoh: HUT Kemerdekaan RI"
                       class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 @error('keterangan') border-rose-500 @enderror">
                @error('keterangan')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-medium text-slate-600 mb-1">Kategori Libur <span class="text-rose-500">*</span></label>
                <select name="jenis" required
                        class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rose-500 @error('jenis') border-rose-500 @enderror">
                    <option value="nasional" {{ old('jenis') === 'nasional' ? 'selected' : '' }}>Libur Nasional</option>
                    <option value="cuti_bersama" {{ old('jenis') === 'cuti_bersama' ? 'selected' : '' }}>Cuti Bersama</option>
                    <option value="khusus" {{ old('jenis') === 'khusus' ? 'selected' : '' }}>Agenda Khusus Sekolah</option>
                </select>
                @error('jenis')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="sm:col-span-2">
                <button type="submit"
                        class="w-full px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    Simpan
                </button>
            </div>
        </form>
    </div>

    {{-- Filter Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <form method="GET" class="flex flex-wrap items-center gap-3" id="libur-filter-form">
            @if($years->isNotEmpty())
            <select name="tahun" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    onchange="document.getElementById('libur-filter-form').submit()">
                <option value="">Semua Tahun</option>
                @foreach($years as $y)
                <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                @endforeach
            </select>
            @endif

            <select name="jenis" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    onchange="document.getElementById('libur-filter-form').submit()">
                <option value="">Semua Kategori</option>
                <option value="nasional" {{ request('jenis') === 'nasional' ? 'selected' : '' }}>Libur Nasional</option>
                <option value="cuti_bersama" {{ request('jenis') === 'cuti_bersama' ? 'selected' : '' }}>Cuti Bersama</option>
                <option value="khusus" {{ request('jenis') === 'khusus' ? 'selected' : '' }}>Agenda Khusus</option>
            </select>

            @if(request('tahun') || request('jenis'))
            <a href="{{ route('admin.hari-libur.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset Filter</a>
            @endif
        </form>

        <div class="text-xs text-slate-500">
            Total Terdaftar: <strong class="text-slate-800">{{ $hariLiburs->total() }} Hari</strong>
        </div>
    </div>

    {{-- Tabel Daftar Hari Libur --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[600px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Tanggal</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Keterangan</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Kategori</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($hariLiburs as $hl)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $hl->jenis === 'nasional' ? 'bg-rose-500' : ($hl->jenis === 'cuti_bersama' ? 'bg-amber-500' : 'bg-indigo-500') }}"></span>
                                <span>{{ $hl->tanggal->translatedFormat('l, d F Y') }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-700 font-medium">
                            {{ $hl->keterangan }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $badgeClass = match($hl->jenis) {
                                    'nasional' => 'bg-rose-50 text-rose-700 border-rose-200',
                                    'cuti_bersama' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'khusus' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                    default => 'bg-slate-50 text-slate-700 border-slate-200'
                                };
                            @endphp
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-medium border {{ $badgeClass }}">
                                {{ $hl->jenis_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.hari-libur.destroy', $hl) }}" 
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur ini?');"
                                  class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold p-1 hover:bg-rose-50 rounded-lg transition-colors">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-slate-400 text-sm">
                            Belum ada data hari libur yang terdaftar.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($hariLiburs->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $hariLiburs->links() }}</div>
        @endif
    </div>

    {{-- Modal Tambah Rentang Libur (Multi-Hari / Kalender Pendidikan) --}}
    <div id="modal-range-libur" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden p-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full p-6 animate-scale-in">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <span class="p-2 bg-amber-50 text-amber-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </span>
                    <h3 class="text-base font-bold text-slate-900">Tambah Rentang Libur Massal</h3>
                </div>
                <button type="button" onclick="document.getElementById('modal-range-libur').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <p class="text-xs text-slate-500 mb-4">
                Gunakan fitur ini untuk menetapkan libur berentang hari (misal: <em>Libur Akhir Semester</em>, <em>Jeda PTS/PAS</em>, atau <em>Libur Hari Raya</em>) tanpa perlu menginput satu per satu.
            </p>

            <form method="POST" action="{{ route('admin.hari-libur.range') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal_mulai" required value="{{ old('tanggal_mulai', now()->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal_selesai" required value="{{ old('tanggal_selesai', now()->addDays(7)->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Keterangan / Nama Agenda Libur <span class="text-rose-500">*</span></label>
                    <input type="text" name="keterangan" required placeholder="Contoh: Libur Akhir Semester Ganjil"
                           class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>

                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Kategori Libur <span class="text-rose-500">*</span></label>
                    <select name="jenis" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="khusus">Agenda Khusus Sekolah / Libur Semester</option>
                        <option value="cuti_bersama">Cuti Bersama</option>
                        <option value="nasional">Libur Nasional</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="document.getElementById('modal-range-libur').classList.add('hidden')" class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors">
                        Simpan Seluruh Rentang Libur
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
