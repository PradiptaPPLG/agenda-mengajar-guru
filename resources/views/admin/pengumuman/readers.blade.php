<x-layouts.admin>
    <x-slot:title>Daftar Pembaca: {{ $pengumuman->judul }}</x-slot:title>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-semibold {{ $pengumuman->tipe_badge_class }}">
                    {{ ucfirst($pengumuman->tipe) }}
                </span>
                <span class="text-xs text-slate-500">
                    Diterbitkan {{ $pengumuman->created_at->format('d M Y, H:i') }}
                </span>
            </div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ $pengumuman->judul }}</h1>
            <p class="text-xs text-slate-500 mt-1">Audit log pengguna yang telah membaca dan mengonfirmasi pengumuman ini.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.pengumuman.index') }}" class="px-3.5 py-2 border border-slate-200 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50 transition-colors">
                &larr; Kembali
            </a>
        </div>
    </div>

    {{-- Stats Card --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Total Pembaca Terkonfirmasi</span>
            <div class="text-2xl font-bold text-blue-600 mt-1">
                {{ number_format($reads->total()) }}
            </div>
            <span class="text-2xs text-slate-400">Pengguna yang telah membuka atau mengklik konfirmasi</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Target Sasaran</span>
            <div class="text-sm font-semibold text-slate-800 mt-1">
                @if(in_array('all', $pengumuman->target_roles ?? []))
                    Semua Pengguna
                @else
                    {{ implode(', ', array_map('ucfirst', $pengumuman->target_roles ?? [])) }}
                @endif
            </div>
            <span class="text-2xs text-slate-400">Tingkat: {{ $pengumuman->target_tingkat === 'all' ? 'Semua Tingkat' : 'Kelas ' . $pengumuman->target_tingkat }}</span>
        </div>

        <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Status Pengumuman</span>
            <div class="mt-1">
                @if($pengumuman->is_active)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif Tayang
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Nonaktif
                    </span>
                @endif
            </div>
            <span class="text-2xs text-slate-400">ID Pengumuman: #{{ $pengumuman->id }}</span>
        </div>
    </div>

    {{-- Readers Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800">Riwayat Pembaca</h2>
            <span class="text-xs text-slate-400">Halaman {{ $reads->currentPage() }} dari {{ $reads->lastPage() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50/75 border-b border-slate-200 text-2xs uppercase tracking-wider text-slate-500 font-bold">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4">Nama Pengguna</th>
                        <th class="py-3 px-4">Peran (Role)</th>
                        <th class="py-3 px-4">Kelas (Jika Siswa)</th>
                        <th class="py-3 px-4">Waktu Dibaca</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reads as $index => $read)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-3 px-4 text-center font-medium text-slate-400">
                                {{ $reads->firstItem() + $index }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $read->user->name ?? 'User Terhapus' }}</div>
                                <div class="text-2xs text-slate-400">{{ $read->user->username ?? $read->user->email ?? '-' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-2xs font-semibold bg-slate-100 text-slate-700 capitalize">
                                    {{ $read->user->role ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-700 font-medium">
                                {{ $read->user->siswaProfile->kelas->nama_kelas ?? '-' }}
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">
                                    {{ $read->read_at ? $read->read_at->format('d/m/Y H:i:s') : '-' }}
                                </div>
                                <div class="text-2xs text-slate-400">
                                    {{ $read->read_at ? $read->read_at->diffForHumans() : '' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                Belum ada pengguna yang tercatat membaca pengumuman ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reads->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $reads->links() }}
            </div>
        @endif
    </div>
</x-layouts.admin>
