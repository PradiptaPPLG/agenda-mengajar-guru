<x-layouts.admin>
    <x-slot:title>Manajemen Status PKL Kelas</x-slot:title>

    <div x-data="{
        editModalOpen: false,
        editData: {
            id: null,
            kelas_id: '',
            tanggal_mulai: '',
            tanggal_selesai: '',
            keterangan: '',
            is_aktif: true,
            actionUrl: ''
        },
        openEdit(item) {
            this.editData = {
                id: item.id,
                kelas_id: item.kelas_id,
                tanggal_mulai: item.tanggal_mulai,
                tanggal_selesai: item.tanggal_selesai,
                keterangan: item.keterangan || '',
                is_aktif: !!item.is_aktif,
                actionUrl: '{{ url('admin/kelas-pkl') }}/' + item.id
            };
            this.editModalOpen = true;
        }
    }">
        {{-- Header Info Alert --}}
        <div class="mb-6 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-2xl p-5 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white text-xs font-semibold backdrop-blur-xs">
                        Prakerin / PKL
                    </span>
                    <span class="text-xs text-blue-100">Solusi Presensi Kelas Industri</span>
                </div>
                <h1 class="text-xl font-bold mt-1">Status & Jadwal PKL Kelas</h1>
                <p class="text-xs text-blue-100 mt-0.5">
                    Kelas yang didaftarkan dalam periode PKL otomatis dinonaktifkan jadwal KBM hariannya sehingga guru pengajar tidak dianggap mangkir/alpa dan persentase kehadiran tetap aman.
                </p>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs">
                <p class="text-xs text-slate-500 font-medium">Total Periode PKL</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-100 p-4 shadow-xs bg-emerald-50/20">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-emerald-700 font-medium">Sedang Berlangsung</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['sedang_berlangsung'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-amber-100 p-4 shadow-xs bg-amber-50/20">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-amber-700 font-medium">Akan Datang</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                </div>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['akan_datang'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-xs bg-slate-50/40">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-slate-500 font-medium">Selesai</p>
                    <span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                </div>
                <p class="text-2xl font-bold text-slate-700 mt-1">{{ $stats['selesai'] }}</p>
            </div>
        </div>

        {{-- Form Tambah Periode PKL Baru --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-5 mb-6 shadow-sm">
            <h3 class="text-base font-semibold text-slate-900 mb-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Tambah Periode PKL Kelas Baru
            </h3>
            <p class="text-xs text-slate-500 mb-4">Pilih kelas dan tentukan rentang tanggal pelaksanaan PKL. Selama periode ini, jadwal kelas tersebut tidak akan muncul di monitoring harian dan siswa tidak dituntut presensi.</p>

            <form method="POST" action="{{ route('admin.kelas-pkl.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                @csrf
                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pilih Kelas 12 <span class="text-rose-500">*</span></label>
                    <select name="kelas_id" required
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('kelas_id') border-rose-500 @enderror">
                        <option value="">-- Pilih Kelas 12 --</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ old('kelas_id') == $k->id ? 'selected' : '' }}>
                                {{ $k->nama }} (Tingkat {{ $k->tingkat }})
                            </option>
                        @endforeach
                    </select>
                    @error('kelas_id')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_mulai" value="{{ old('tanggal_mulai', now()->toDateString()) }}" required
                           class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tanggal_mulai') border-rose-500 @enderror">
                    @error('tanggal_mulai')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                    <input type="date" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}" required
                           class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tanggal_selesai') border-rose-500 @enderror">
                    @error('tanggal_selesai')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Keterangan (Opsional)</label>
                    <input type="text" name="keterangan" value="{{ old('keterangan') }}" placeholder="Contoh: PKL Gelombang 1 DKV"
                           class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('keterangan') border-rose-500 @enderror">
                    @error('keterangan')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm cursor-pointer">
                        Simpan Periode
                    </button>
                </div>
            </form>
        </div>

        {{-- Filter Form --}}
        <form method="GET" action="{{ route('admin.kelas-pkl.index') }}" class="flex flex-wrap gap-3 mb-4" id="pkl-filter-form">
            <select name="kelas_id" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    onchange="document.getElementById('pkl-filter-form').submit()">
                <option value="">Semua Kelas 12</option>
                @foreach($kelasList as $k)
                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>
                        {{ $k->nama }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="px-3.5 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    onchange="document.getElementById('pkl-filter-form').submit()">
                <option value="">Semua Status</option>
                <option value="sedang_berlangsung" {{ request('status') === 'sedang_berlangsung' ? 'selected' : '' }}>🟢 Sedang Berlangsung</option>
                <option value="akan_datang" {{ request('status') === 'akan_datang' ? 'selected' : '' }}>🟡 Akan Datang</option>
                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>⚪ Selesai</option>
                <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>🔴 Nonaktif</option>
            </select>

            @if(request('kelas_id') || request('status'))
                <a href="{{ route('admin.kelas-pkl.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Reset Filter
                </a>
            @endif
        </form>

        {{-- Tabel Daftar Periode PKL --}}
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[700px]">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Kelas</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Periode PKL</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Durasi</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Keterangan</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Status</th>
                            <th class="text-right px-4 py-3 font-semibold text-slate-600 whitespace-nowrap">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($pklList as $item)
                            @php
                                $badge = $item->status_badge;
                                $diffDays = $item->tanggal_mulai && $item->tanggal_selesai
                                    ? $item->tanggal_mulai->diffInDays($item->tanggal_selesai) + 1
                                    : 0;
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 font-semibold text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-700 flex items-center justify-center font-bold text-xs">
                                            {{ $item->kelas->tingkat ?? '-' }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $item->kelas->nama ?? '-' }}</p>
                                            <p class="text-xs text-slate-400">Tahun Ajaran {{ $item->kelas->tahun_ajaran ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div class="flex items-center gap-1.5 font-medium">
                                        <span>{{ $item->tanggal_mulai->translatedFormat('d M Y') }}</span>
                                        <span class="text-slate-400">&rarr;</span>
                                        <span>{{ $item->tanggal_selesai->translatedFormat('d M Y') }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 text-xs">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 font-medium">
                                        {{ $diffDays }} Hari
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600 text-xs">
                                    {{ $item->keterangan ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badge['class'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        {{-- Toggle Aktif Button --}}
                                        <form method="POST" action="{{ route('admin.kelas-pkl.toggle', $item) }}">
                                            @csrf
                                            <button type="submit" 
                                                    title="{{ $item->is_aktif ? 'Nonaktifkan PKL' : 'Aktifkan PKL' }}"
                                                    class="p-1.5 rounded-lg border text-xs font-medium transition-colors {{ $item->is_aktif ? 'text-amber-700 border-amber-200 hover:bg-amber-50' : 'text-emerald-700 border-emerald-200 hover:bg-emerald-50' }}">
                                                @if($item->is_aktif)
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                @endif
                                            </button>
                                        </form>

                                        {{-- Edit Button --}}
                                        <button type="button"
                                                @click="openEdit(@js([
                                                    'id' => $item->id,
                                                    'kelas_id' => $item->kelas_id,
                                                    'tanggal_mulai' => $item->tanggal_mulai->toDateString(),
                                                    'tanggal_selesai' => $item->tanggal_selesai->toDateString(),
                                                    'keterangan' => $item->keterangan ?? '',
                                                    'is_aktif' => (bool) $item->is_aktif,
                                                ]))"
                                                class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 transition-colors"
                                                title="Edit Periode PKL">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        </button>

                                        {{-- Delete Button --}}
                                        <form method="POST" action="{{ route('admin.kelas-pkl.destroy', $item) }}" 
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus data periode PKL ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-1.5 rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 transition-colors"
                                                    title="Hapus Periode">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500 text-sm">
                                    <div class="max-w-xs mx-auto text-center">
                                        <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                        <p class="font-medium text-slate-700">Belum ada kelas berstatus PKL</p>
                                        <p class="text-xs text-slate-400 mt-1">Gunakan form di atas untuk mendaftarkan kelas yang sedang melaksanakan PKL.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($pklList->hasPages())
                <div class="px-4 py-3 border-t border-slate-200">
                    {{ $pklList->links() }}
                </div>
            @endif
        </div>

        {{-- Modal Edit Periode PKL --}}
        <div x-show="editModalOpen" 
             style="display: none;"
             class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div @click.away="editModalOpen = false" 
                 class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-xl border border-slate-100">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit Periode PKL Kelas
                    </h3>
                    <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form :action="editData.actionUrl" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kelas 12 <span class="text-rose-500">*</span></label>
                        <select name="kelas_id" x-model="editData.kelas_id" required
                                class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($kelasList as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }} (Tingkat {{ $k->tingkat }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Mulai <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal_mulai" x-model="editData.tanggal_mulai" required
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Selesai <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal_selesai" x-model="editData.tanggal_selesai" required
                                   class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan</label>
                        <input type="text" name="keterangan" x-model="editData.keterangan" placeholder="Contoh: PKL Gelombang 1"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" name="is_aktif" id="edit_is_aktif" value="1" x-model="editData.is_aktif"
                               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <label for="edit_is_aktif" class="text-xs font-medium text-slate-700">Aktifkan periode ini (Jadwal nonaktif saat aktif)</label>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                        <button type="button" @click="editModalOpen = false"
                                class="px-4 py-2 border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold rounded-xl transition-colors">
                            Batal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
