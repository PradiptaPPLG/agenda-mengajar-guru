<x-layouts.admin>
    <x-slot:title>Mata Pelajaran</x-slot:title>
    <x-slot:actions>
        <a href="{{ route('admin.mata-pelajaran.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah
        </a>
    </x-slot:actions>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <form action="{{ route('admin.mata-pelajaran.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto" x-data="{ includeAdaptif: {{ request('include_adaptif') ? 'true' : 'false' }} }">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari pelajaran..." 
                           class="w-full sm:w-64 pl-9 pr-4 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
                    <input type="checkbox" name="include_adaptif" value="1" x-model="includeAdaptif" @change="$el.form.submit()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Sertakan mata pelajaran adaptif (jurusan)
                </label>
                <noscript><button type="submit" class="px-3 py-2 bg-slate-200 rounded text-sm">Filter</button></noscript>
            </form>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Nama</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Kode</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Jenis</th>
                    <th class="text-right px-4 py-3 font-semibold text-slate-600">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($mataPelajarans as $mp)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <p class="font-medium text-slate-900">{{ $mp->nama }}</p>
                        @if($mp->jenis === 'adaptif')
                            <p class="text-xs text-slate-500 mt-0.5">Ditetapkan ke {{ $mp->kelas->count() }} kelas</p>
                        @endif
                    </td>
                    <td class="px-4 py-3"><span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded">{{ $mp->kode }}</span></td>
                    <td class="px-4 py-3">
                        @if($mp->jenis === 'adaptif')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                Adaptif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                                Normatif
                            </span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end">
                            <x-action-dropdown 
                                :editUrl="route('admin.mata-pelajaran.edit', $mp)" 
                                :deleteUrl="route('admin.mata-pelajaran.destroy', $mp)" 
                            />
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-10 text-center text-slate-400 text-sm">Belum ada mata pelajaran</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($mataPelajarans->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">{{ $mataPelajarans->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
