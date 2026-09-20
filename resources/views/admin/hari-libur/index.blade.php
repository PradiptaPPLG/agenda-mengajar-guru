<x-layouts.admin>
    <x-slot:title>Kalender Hari Libur</x-slot:title>

    {{-- Form Tambah Hari Libur --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 shadow-sm">
        <h3 class="text-base font-semibold text-slate-900 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Tambah Hari Libur Baru
        </h3>
        <p class="text-xs text-slate-500 mb-4">Tanggal yang didaftarkan sebagai hari libur akan membebaskan kewajiban presensi KBM dan tidak dihitung sebagai Alpa pada laporan rekapitulasi.</p>
        
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

    {{-- Filter --}}
    <form method="GET" class="flex flex-wrap gap-3 mb-4">
        @if($years->isNotEmpty())
        <select name="tahun" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Tahun</option>
            @foreach($years as $y)
            <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
            @endforeach
        </select>
        @endif

        <select name="jenis" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Kategori</option>
            <option value="nasional" {{ request('jenis') === 'nasional' ? 'selected' : '' }}>Libur Nasional</option>
            <option value="cuti_bersama" {{ request('jenis') === 'cuti_bersama' ? 'selected' : '' }}>Cuti Bersama</option>
            <option value="khusus" {{ request('jenis') === 'khusus' ? 'selected' : '' }}>Agenda Khusus</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-sm font-medium rounded-xl hover:bg-slate-800 transition-colors">Filter</button>
    </form>

    {{-- Tabel Daftar Hari Libur --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Tanggal</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Keterangan</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Kategori</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($hariLiburs as $hl)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-medium text-slate-900">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
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
        @if($hariLiburs->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $hariLiburs->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
