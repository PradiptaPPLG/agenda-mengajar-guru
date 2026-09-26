<x-layouts.admin>
    <x-slot:title>Edit Jadwal</x-slot:title>
    <div class="max-w-lg">
        <div class="bg-white rounded-2xl border border-slate-200 p-6">
            <form action="{{ route('admin.jadwal.update', $jadwal) }}" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Tahun Ajaran <span class="text-red-500">*</span></label>
                        <input type="text" name="tahun_ajaran" value="{{ old('tahun_ajaran', $jadwal->tahun_ajaran ?? Setting::getTahunAjaranAktif()) }}" required placeholder="Contoh: 2026/2027"
                               class="w-full px-3.5 py-2.5 border {{ $errors->has('tahun_ajaran') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('tahun_ajaran')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Semester <span class="text-red-500">*</span></label>
                        <select name="semester" required class="w-full px-3.5 py-2.5 border {{ $errors->has('semester') ? 'border-red-400' : 'border-slate-200' }} rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($daftarSemester as $val => $label)
                            <option value="{{ $val }}" {{ old('semester', $jadwal->semester ?? Setting::getSemesterAktif()) == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('semester')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelas <span class="text-red-500">*</span></label>
                    <select name="kelas_id" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ old('kelas_id', $jadwal->kelas_id) == $k->id ? 'selected' : '' }}>{{ $k->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Guru <span class="text-red-500">*</span></label>
                    <select name="guru_id" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($guruList as $g)
                        <option value="{{ $g->id }}" {{ old('guru_id', $jadwal->guru_id) == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Mata Pelajaran <span class="text-red-500">*</span></label>
                    <select name="mata_pelajaran_id" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($mataPelajarans as $mp)
                        <option value="{{ $mp->id }}" {{ old('mata_pelajaran_id', $jadwal->mata_pelajaran_id) == $mp->id ? 'selected' : '' }}>{{ $mp->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Hari <span class="text-red-500">*</span></label>
                    <select name="hari" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\JadwalPelajaran::$namaHari as $num => $nama)
                        <option value="{{ $num }}" {{ old('hari', $jadwal->hari) == $num ? 'selected' : '' }}>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Jam Mulai</label>
                        <input type="time" name="jam_mulai" value="{{ old('jam_mulai', $jadwal->jam_mulai) }}" required
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Jam Selesai</label>
                        <input type="time" name="jam_selesai" value="{{ old('jam_selesai', $jadwal->jam_selesai) }}" required
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kelompok Blok</label>
                    <select name="kelompok_blok"
                        class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="reguler" {{ old('kelompok_blok', $jadwal->kelompok_blok) == 'reguler' ? 'selected' : '' }}>Reguler (Tidak Sistem Blok)</option>
                        <option value="kelompok_a" {{ old('kelompok_blok', $jadwal->kelompok_blok) == 'kelompok_a' ? 'selected' : '' }}>📘 Kelompok A — Umum (PAI, PKN, Matematika, dst)</option>
                        <option value="kelompok_b" {{ old('kelompok_blok', $jadwal->kelompok_blok) == 'kelompok_b' ? 'selected' : '' }}>🔧 Kelompok B — Produktif (Informatika, PKK, dst)</option>
                    </select>
                    @error('kelompok_blok')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors">Perbarui</button>
                    <a href="{{ route('admin.jadwal.index') }}" class="px-6 py-2.5 border border-slate-200 text-slate-700 text-sm font-medium rounded-xl hover:bg-slate-50 transition-colors">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
