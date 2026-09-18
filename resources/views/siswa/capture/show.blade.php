<x-layouts.siswa>
    <x-slot:title>Foto Bukti</x-slot:title>

    <div class="px-4 py-4 space-y-4">
        <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>

        {{-- Pelajaran info --}}
        <div class="bg-emerald-600 rounded-2xl p-4 text-white">
            <p class="text-lg font-bold">{{ $pertemuan->jadwal->mataPelajaran->nama }}</p>
            <p class="text-sm text-emerald-100 mt-0.5">Guru: {{ $pertemuan->jadwal->guru->name }}</p>
            <p class="text-sm text-emerald-100 mt-0.5">{{ $pertemuan->tanggal->translatedFormat('l, d F Y') }}</p>
        </div>

        {{-- Teacher status from the teacher's own record --}}
        @if($pertemuan->kehadiranGuru)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl
                        {{ match($pertemuan->kehadiranGuru->status) {
                            'hadir' => 'bg-emerald-100',
                            'sakit' => 'bg-amber-100',
                            'dispensasi' => 'bg-purple-100',
                            default => 'bg-red-100',
                        } }}
                        flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 {{ match($pertemuan->kehadiranGuru->status) {
                            'hadir' => 'text-emerald-600',
                            'sakit' => 'text-amber-600',
                            'dispensasi' => 'text-purple-600',
                            default => 'text-red-600',
                        } }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Status Kehadiran Guru</p>
                <p class="text-sm font-semibold text-slate-900">{{ $pertemuan->kehadiranGuru->status_label }}</p>
            </div>
        </div>
        @endif

        {{-- Existing capture preview --}}
        @if($existingCapture)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100">
                <p class="text-sm font-semibold text-slate-900">Foto Bukti Anda</p>
            </div>
            <div class="p-4 bg-slate-50 flex flex-col items-center">
                <img src="{{ Storage::url($existingCapture->foto_path) }}" alt="Foto Bukti"
                     class="w-full h-auto max-h-64 object-contain rounded-xl">
                <div class="mt-3 flex items-center gap-2">
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full
                        {{ match($existingCapture->status_guru_dilaporkan) {
                            'hadir' => 'badge-hadir',
                            'sakit' => 'badge-sakit',
                            'dispensasi' => 'badge-dispensasi',
                            default => 'badge-alpa',
                        } }}">
                        Dilaporkan: {{ ucfirst($existingCapture->status_guru_dilaporkan) }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        {{-- Upload form --}}
        @if(!$isPast)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">
                    {{ $existingCapture ? 'Perbarui Foto Bukti' : 'Upload Foto Bukti' }}
                </h2>
            </div>
            <form action="{{ route('siswa.capture.store', ['jadwal' => $pertemuan->jadwal_id, 'tanggal' => $pertemuan->tanggal->toDateString()]) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-6" id="capture-form">
                @csrf

                {{-- Camera / Gallery input --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Foto Bukti</label>
                    <div id="preview-container" class="hidden mb-3 bg-slate-50 rounded-xl border border-slate-200 overflow-hidden">
                        <img id="preview-img" src="#" alt="Preview" class="w-full h-auto max-h-64 object-contain">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex flex-col items-center justify-center gap-2 py-4 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-colors">
                            <input type="file" name="foto" accept="image/*" capture="environment"
                                   class="sr-only" id="camera-input" onchange="previewPhoto(this)">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-xs font-medium text-slate-600">Kamera</span>
                        </label>
                        <label class="flex flex-col items-center justify-center gap-2 py-4 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-colors">
                            <input type="file" name="foto" accept="image/*"
                                   class="sr-only" id="gallery-input" onchange="previewPhoto(this)">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-xs font-medium text-slate-600">Galeri</span>
                        </label>
                    </div>
                    @error('foto')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Status guru --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Status Guru yang Anda Lihat</label>
                    <div class="grid grid-cols-4 gap-2">
                        @php
                            $statusStyles = [
                                'hadir' => 'peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:border-emerald-300',
                                'sakit' => 'peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 hover:border-amber-300',
                                'alpa' => 'peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:border-red-300',
                                'dispensasi' => 'peer-checked:border-purple-500 peer-checked:bg-purple-50 peer-checked:text-purple-700 hover:border-purple-300',
                            ];
                        @endphp
                        @foreach(['hadir' => 'Hadir', 'sakit' => 'Sakit', 'alpa' => 'Alpa', 'dispensasi' => 'Dispensasi'] as $val => $label)
                        <label class="relative">
                            <input type="radio" name="status_guru_dilaporkan" value="{{ $val }}" class="sr-only peer"
                                   {{ (old('status_guru_dilaporkan') ?? $existingCapture?->status_guru_dilaporkan) === $val ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200 cursor-pointer transition-all
                                        {{ $statusStyles[$val] }}">
                                <span class="text-sm font-semibold">{{ $label }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('status_guru_dilaporkan')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Alpa detail --}}
                <div id="alpa-guru-section" class="{{ (old('status_guru_dilaporkan') ?? $existingCapture?->status_guru_dilaporkan) === 'alpa' ? '' : 'hidden' }} space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-2">Jenis Ketidakhadiran Guru</label>
                        <div class="space-y-2">
                            @foreach(['ada_tugas' => 'Ada Tugas', 'tanpa_tugas' => 'Tanpa Tugas', 'guru_pengganti' => 'Ada Guru Pengganti'] as $val => $label)
                            <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 has-[:checked]:border-emerald-400 has-[:checked]:bg-emerald-50">
                                <input type="radio" name="jenis_alpa_dilaporkan" value="{{ $val }}" class="text-emerald-600"
                                       {{ (old('jenis_alpa_dilaporkan') ?? $existingCapture?->jenis_alpa_dilaporkan) === $val ? 'checked' : '' }}
                                       onchange="handleJenisAlpaGuru(this.value)">
                                <span class="text-sm text-slate-700">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('jenis_alpa_dilaporkan')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div id="guru-pengganti-siswa-section" class="{{ (old('jenis_alpa_dilaporkan') ?? $existingCapture?->jenis_alpa_dilaporkan) === 'guru_pengganti' ? '' : 'hidden' }}">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Guru Pengganti</label>
                        <input type="text" name="guru_pengganti_nama"
                               value="{{ old('guru_pengganti_nama') ?? $existingCapture?->guru_pengganti_nama }}"
                               placeholder="Nama guru pengganti..."
                               class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                    {{ $existingCapture ? 'Perbarui Foto Bukti' : 'Kirim Foto Bukti' }}
                </button>
            </form>
        </div>
        @else
        <div class="bg-red-50 border border-red-200 rounded-2xl p-5 text-center">
            <svg class="w-10 h-10 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-red-800 font-semibold mb-1">Jam Pelajaran Telah Berakhir</p>
            <p class="text-red-600 text-sm">Waktu untuk mengirimkan atau mengubah foto bukti sudah ditutup.</p>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function previewPhoto(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById('preview-img').src = e.target.result;
                    document.getElementById('preview-container').classList.remove('hidden');
                    // Sync the other input (camera/gallery) - both use name="foto"
                    // Only last selected file counts
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        function handleStatusGuru(val) {
            document.getElementById('alpa-guru-section').classList.toggle('hidden', val !== 'alpa');
        }
        function handleJenisAlpaGuru(val) {
            document.getElementById('guru-pengganti-siswa-section').classList.toggle('hidden', val !== 'guru_pengganti');
        }
    </script>
    @endpush
</x-layouts.siswa>
