<x-layouts.admin>
    <x-slot:title>Edit Mata Pelajaran</x-slot:title>
    <div class="max-w-md">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <form action="{{ route('admin.mata-pelajaran.update', $mataPelajaran) }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="nama" value="{{ old('nama', $mataPelajaran->nama) }}" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('nama')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kode <span class="text-red-500">*</span></label>
                    <input type="text" name="kode" value="{{ old('kode', $mataPelajaran->kode) }}" required maxlength="20"
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm font-mono uppercase focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('kode')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                
                <div x-data="{ jenis: '{{ old('jenis', $mataPelajaran->jenis) }}' }">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="jenis" value="normatif" x-model="jenis" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                            Normatif (Umum)
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="jenis" value="adaptif" x-model="jenis" class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                            Adaptif (Kejuruan)
                        </label>
                    </div>
                    @error('jenis')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                    <div x-show="jenis === 'adaptif'" class="mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl" style="display: none;">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Pilih Kelas yang Mendapatkan Pelajaran Ini</label>
                        <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto pr-2">
                            @php
                                $selectedKelasIds = old('kelas_ids', $mataPelajaran->kelas->pluck('id')->toArray());
                            @endphp
                            @foreach($kelasList as $kelas)
                            <label class="flex items-start gap-2 text-sm p-2 rounded hover:bg-slate-100 cursor-pointer">
                                <input type="checkbox" name="kelas_ids[]" value="{{ $kelas->id }}" 
                                       {{ in_array($kelas->id, $selectedKelasIds) ? 'checked' : '' }}
                                       class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="leading-tight">{{ $kelas->nama }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('kelas_ids')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">Perbarui</button>
                    <a href="{{ route('admin.mata-pelajaran.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
