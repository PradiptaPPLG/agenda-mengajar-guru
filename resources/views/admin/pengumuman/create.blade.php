<x-layouts.admin>
    <x-slot:title>Terbitkan Pengumuman Baru</x-slot:title>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Terbitkan Pengumuman Baru</h1>
            <p class="text-xs text-slate-500 mt-1">Buat informasi, surat edaran, atau pengumuman agenda sekolah.</p>
        </div>
        <a href="{{ route('admin.pengumuman.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900">
            &larr; Kembali ke Daftar
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm max-w-3xl">
        <form method="POST" action="{{ route('admin.pengumuman.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Judul --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Pengumuman <span class="text-rose-500">*</span></label>
                <input type="text" name="judul" value="{{ old('judul') }}" required placeholder="Contoh: Pemadatan Jam KBM Hari Kamis Karena Rapat Pleno"
                       class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('judul') border-rose-500 @enderror">
                @error('judul')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Tingkat Urgensi & Target Tingkat --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Tingkat Urgensi <span class="text-rose-500">*</span></label>
                    <select name="tipe" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="info" {{ old('tipe') === 'info' ? 'selected' : '' }}>Info Biasa (Badge Biru)</option>
                        <option value="penting" {{ old('tipe') === 'penting' ? 'selected' : '' }}>Penting / Perhatian (Badge Kuning)</option>
                        <option value="urgent" {{ old('tipe') === 'urgent' ? 'selected' : '' }}>Mendesak / Darurat (Badge Merah)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Target Tingkat Siswa (Opsional)</label>
                    <select name="target_tingkat" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="all" {{ old('target_tingkat') === 'all' ? 'selected' : '' }}>Semua Tingkat (10, 11, 12)</option>
                        <option value="10" {{ old('target_tingkat') === '10' ? 'selected' : '' }}>Khusus Kelas 10 (Fase E)</option>
                        <option value="11" {{ old('target_tingkat') === '11' ? 'selected' : '' }}>Khusus Kelas 11 (Fase F)</option>
                        <option value="12" {{ old('target_tingkat') === '12' ? 'selected' : '' }}>Khusus Kelas 12 (Fase F Lanjutan)</option>
                    </select>
                </div>
            </div>

            {{-- Sasaran Role Pengguna --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Target Sasaran Penerima <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="target_roles[]" value="all" {{ in_array('all', old('target_roles', ['all'])) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Semua Pengguna</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="target_roles[]" value="guru" {{ in_array('guru', old('target_roles', [])) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Guru Pengajar</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="target_roles[]" value="siswa" {{ in_array('siswa', old('target_roles', [])) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Siswa</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="target_roles[]" value="piket" {{ in_array('piket', old('target_roles', [])) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Petugas Piket</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="target_roles[]" value="tu" {{ in_array('tu', old('target_roles', [])) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Tata Usaha (TU)</span>
                    </label>
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="target_roles[]" value="bk" {{ in_array('bk', old('target_roles', [])) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Kesiswaan / Guru BK</span>
                    </label>
                </div>
                @error('target_roles')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Isi Pengumuman --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Isi Lengkap Pengumuman <span class="text-rose-500">*</span></label>
                <textarea name="isi" rows="5" required placeholder="Tuliskan isi pengumuman, instruksi, atau jadwal agenda secara jelas di sini..."
                          class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('isi') border-rose-500 @enderror">{{ old('isi') }}</textarea>
                @error('isi')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Masa Tayang (Mulai & Selesai) --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mulai Ditayangkan <span class="text-rose-500">*</span></label>
                    <input type="datetime-local" name="tanggal_mulai" value="{{ old('tanggal_mulai', now()->format('Y-m-d\TH:i')) }}" required
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Selesai / Kedaluwarsa (Opsional)</label>
                    <input type="datetime-local" name="tanggal_selesai" value="{{ old('tanggal_selesai') }}"
                           class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-2xs text-slate-400 mt-1">Kosongkan jika ingin ditayangkan seterusnya sampai di-nonaktifkan manual.</p>
                </div>
            </div>

            {{-- Lampiran Berkas --}}
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Lampiran Dokumen / Surat Edaran (Opsional)</label>
                <input type="file" name="file_lampiran" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx"
                       class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                <p class="text-2xs text-slate-400 mt-1">Format: PDF, Word, Excel, atau Gambar (Maks. 5 MB).</p>
                @error('file_lampiran')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Pinned & Active Toggles --}}
            <div class="flex flex-wrap items-center gap-6 pt-2 border-t border-slate-100">
                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }} class="rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                    <span>Sematkan di paling atas (Pin to Top)</span>
                </label>

                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span>Langsung Terbitkan (Status Aktif)</span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.pengumuman.index') }}" class="px-4 py-2.5 border border-slate-200 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50 transition-colors">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-colors">
                    Terbitkan Pengumuman
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
