<x-layouts.admin>
    <x-slot:title>Papan Pengumuman Sekolah</x-slot:title>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                </svg>
                Papan Pengumuman & Agenda Digital
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kirim informasi penting, surat edaran, atau agenda sekolah langsung ke dashboard Guru, Siswa, dan Petugas Piket.
            </p>
        </div>

        <a href="{{ route('admin.pengumuman.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>+ Terbitkan Pengumuman Baru</span>
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <form method="GET" class="flex flex-wrap items-center gap-3" id="pengumuman-filter-form">
            <select name="tipe" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50"
                    onchange="document.getElementById('pengumuman-filter-form').submit()">
                <option value="">Semua Tingkat Urgensi</option>
                <option value="info" {{ request('tipe') === 'info' ? 'selected' : '' }}>Info Biasa (Biru)</option>
                <option value="penting" {{ request('tipe') === 'penting' ? 'selected' : '' }}>Penting (Kuning)</option>
                <option value="urgent" {{ request('tipe') === 'urgent' ? 'selected' : '' }}>Mendesak / Urgent (Merah)</option>
            </select>

            <select name="status" class="px-3.5 py-2 border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50"
                    onchange="document.getElementById('pengumuman-filter-form').submit()">
                <option value="">Semua Status Tayang</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Sedang Tayang (Aktif)</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif / Berakhir</option>
            </select>

            @if(request('tipe') || request('status'))
            <a href="{{ route('admin.pengumuman.index') }}" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset Filter</a>
            @endif
        </form>

        <div class="text-xs text-slate-500">
            Total Pengumuman: <strong class="text-slate-800">{{ $pengumumans->total() }}</strong>
        </div>
    </div>

    {{-- Tabel Pengumuman --}}
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[700px]">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Pengumuman & Sasaran</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600">Masa Tayang</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600">Keterbacaan</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-600">Status</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pengumumans as $p)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3.5">
                            <div class="flex items-start gap-2.5">
                                @if($p->is_pinned)
                                <span class="text-amber-500 mt-0.5" title="Disematkan di atas">
                                    <svg class="w-4 h-4 fill-amber-500" viewBox="0 0 20 20"><path d="M10 2a1 1 0 011 1v1.323l3.954 1.582 1.599-.8a1 1 0 01.894 1.79l-1.233.616 1.738 5.42a1 1 0 01-.285 1.05A3.989 3.989 0 0115 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.715-5.347L11 6.47V15a1 1 0 11-2 0V6.47L6.237 7.584l1.715 5.347a1 1 0 01-.285 1.05A3.989 3.989 0 015 15a3.989 3.989 0 01-2.667-1.019 1 1 0 01-.285-1.05l1.738-5.42-1.233-.617a1 1 0 01.894-1.789l1.599.799L9 4.323V3a1 1 0 011-1z"/></svg>
                                </span>
                                @endif
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs px-2 py-0.5 rounded-full font-bold uppercase tracking-wider border {{ $p->tipe_badge_class }}">
                                            {{ $p->tipe }}
                                        </span>
                                        <span class="font-semibold text-slate-900">{{ $p->judul }}</span>
                                    </div>
                                    <div class="text-xs text-slate-500 line-clamp-1 mb-1.5">
                                        {{ Str::limit(strip_tags($p->isi), 90) }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-1.5 text-2xs">
                                        <span class="text-slate-400">Sasaran:</span>
                                        @foreach($p->target_roles ?? ['all'] as $role)
                                        <span class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-medium capitalize">
                                            {{ $role === 'all' ? 'Semua Pengguna' : str_replace('_', ' ', $role) }}
                                        </span>
                                        @endforeach
                                        @if($p->target_tingkat && $p->target_tingkat !== 'all')
                                        <span class="bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded font-medium">
                                            Kelas {{ $p->target_tingkat }}
                                        </span>
                                        @endif
                                        @if($p->file_lampiran)
                                        <a href="{{ asset('storage/'.$p->file_lampiran) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:underline">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            Lampiran Berkas
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-xs text-slate-600 whitespace-nowrap">
                            <div>Mulai: <strong>{{ $p->tanggal_mulai->format('d/m/Y H:i') }}</strong></div>
                            <div class="text-slate-400 mt-0.5">
                                Sampai: {{ $p->tanggal_selesai ? $p->tanggal_selesai->format('d/m/Y H:i') : 'Seterusnya' }}
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            <a href="{{ route('admin.pengumuman.readers', $p) }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-700 text-xs font-semibold transition-colors">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>{{ $p->reads_count }} Pembaca</span>
                            </a>
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            @php
                                $isExpired = $p->tanggal_selesai && $p->tanggal_selesai->isPast();
                            @endphp
                            @if(! $p->is_active)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-slate-100 text-slate-500">Nonaktif</span>
                            @elseif($isExpired)
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-50 text-amber-600 border border-amber-200">Kedaluwarsa</span>
                            @else
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Tayang</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.pengumuman.edit', $p) }}" class="text-blue-600 hover:text-blue-800 text-xs font-semibold p-1 hover:bg-blue-50 rounded-lg transition-colors">
                                    Edit
                                </a>
                                <form method="POST" action="{{ route('admin.pengumuman.destroy', $p) }}" 
                                      onsubmit="return confirm('Hapus pengumuman ini? Data keterbacaan juga akan terhapus.');"
                                      class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs font-semibold p-1 hover:bg-rose-50 rounded-lg transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-sm">
                            Belum ada pengumuman yang diterbitkan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pengumumans->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $pengumumans->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
