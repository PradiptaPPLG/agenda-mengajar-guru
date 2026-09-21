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

        {{-- Teacher status from the teacher's own record / student sync --}}
        @if($pertemuan->kehadiranGuru)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl
                        {{ match($pertemuan->kehadiranGuru->status) {
                            'hadir' => 'bg-emerald-100',
                            'terlambat' => 'bg-amber-100',
                            'sakit' => 'bg-amber-100',
                            'dispensasi' => 'bg-purple-100',
                            default => 'bg-red-100',
                        } }}
                        flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 {{ match($pertemuan->kehadiranGuru->status) {
                            'hadir' => 'text-emerald-600',
                            'terlambat' => 'text-amber-600',
                            'sakit' => 'text-amber-600',
                            'dispensasi' => 'text-purple-600',
                            default => 'text-red-600',
                        } }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs text-slate-500 font-medium">Status Kehadiran Guru Terkini</p>
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-slate-900">{{ $pertemuan->kehadiranGuru->status_label }}</p>
                    @if($pertemuan->kehadiranGuru->alasan_tidak_hadir)
                        <span class="text-xs px-2 py-0.5 rounded-md bg-red-50 text-red-700 font-medium border border-red-100">
                            {{ $pertemuan->kehadiranGuru->alasan_tidak_hadir_label }}
                        </span>
                    @endif
                </div>
                @if($pertemuan->kehadiranGuru->guru_pengganti_nama)
                    <p class="text-xs text-slate-600 mt-0.5">Guru Pengganti: <span class="font-medium">{{ $pertemuan->kehadiranGuru->guru_pengganti_nama }}</span></p>
                @endif
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
                <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                    <span class="text-xs font-medium px-2.5 py-1 rounded-full
                        {{ match($existingCapture->status_guru_dilaporkan) {
                            'hadir' => 'badge-hadir',
                            'terlambat' => 'badge-sakit',
                            'sakit' => 'badge-sakit',
                            'dispensasi' => 'badge-dispensasi',
                            default => 'badge-alpa',
                        } }}">
                        Dilaporkan: {{ match($existingCapture->status_guru_dilaporkan) {
                            'hadir' => 'Hadir',
                            'terlambat' => 'Terlambat',
                            'tidak_hadir' => 'Tidak Hadir',
                            default => ucfirst($existingCapture->status_guru_dilaporkan)
                        } }}
                    </span>
                    @if($existingCapture->alasan_tidak_hadir)
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-100 text-red-800">
                            Alasan: {{ match($existingCapture->alasan_tidak_hadir) {
                                'sakit' => 'Sakit',
                                'izin' => 'Izin',
                                'rapat_dinas' => 'Rapat Dinas',
                                'dinas_luar' => 'Dinas Luar',
                                'tugas_luar' => 'Tugas Luar',
                                'tanpa_keterangan' => 'Tanpa Keterangan',
                                default => ucfirst($existingCapture->alasan_tidak_hadir)
                            } }}
                        </span>
                    @endif
                    @if($existingCapture->guru_pengganti_nama)
                        <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-100 text-blue-800">
                            Pengganti: {{ $existingCapture->guru_pengganti_nama }}
                        </span>
                    @endif
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

                {{-- Camera / Gallery input & Client-Side Compression --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Foto Bukti</label>
                    
                    {{-- Hidden final input submitted with form --}}
                    <input type="file" name="foto" id="final-foto-input" class="sr-only" {{ $existingCapture ? '' : 'required' }}>

                    {{-- Preview container with compression feedback --}}
                    <div id="preview-container" class="hidden mb-3 bg-slate-50 rounded-xl border border-slate-200 overflow-hidden">
                        <div class="relative">
                            <img id="preview-img" src="#" alt="Preview" class="w-full h-auto max-h-64 object-contain">
                            <div id="compressing-indicator" class="hidden absolute inset-0 bg-slate-900/40 backdrop-blur-xs flex flex-col items-center justify-center text-white text-xs font-medium">
                                <svg class="animate-spin h-6 w-6 text-white mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Mengompresi foto hemat kuota...
                            </div>
                        </div>
                        <div id="compression-status" class="hidden px-3 py-2 bg-emerald-50 border-t border-emerald-100 flex items-center justify-between text-[11px] text-emerald-800">
                            <span class="flex items-center gap-1 font-semibold">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                WebP Hemat Kuota
                            </span>
                            <span id="compression-summary" class="font-medium text-emerald-700"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex flex-col items-center justify-center gap-2 py-4 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-colors">
                            <input type="file" accept="image/*" capture="environment"
                                   class="sr-only" id="camera-input" onchange="handlePhotoSelection(this)">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-xs font-medium text-slate-600">Kamera</span>
                        </label>
                        <label class="flex flex-col items-center justify-center gap-2 py-4 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-colors">
                            <input type="file" accept="image/*"
                                   class="sr-only" id="gallery-input" onchange="handlePhotoSelection(this)">
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
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Status Guru yang Anda Laporkan</label>
                    <div class="grid grid-cols-3 gap-2">
                        @php
                            $curStatus = old('status_guru_dilaporkan') ?? $existingCapture?->status_guru_dilaporkan ?? 'hadir';
                            // Normalize legacy status if needed
                            if (in_array($curStatus, ['sakit', 'alpa', 'dispensasi'])) {
                                $curStatus = 'tidak_hadir';
                            }
                        @endphp
                        {{-- Hadir --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="status_guru_dilaporkan" value="hadir" class="sr-only peer"
                                   {{ $curStatus === 'hadir' ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700 hover:border-emerald-300 transition-all">
                                <span class="text-sm font-semibold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Hadir
                                </span>
                            </div>
                        </label>

                        {{-- Terlambat --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="status_guru_dilaporkan" value="terlambat" class="sr-only peer"
                                   {{ $curStatus === 'terlambat' ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700 hover:border-amber-300 transition-all">
                                <span class="text-sm font-semibold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Terlambat
                                </span>
                            </div>
                        </label>

                        {{-- Tidak Hadir --}}
                        <label class="relative cursor-pointer">
                            <input type="radio" name="status_guru_dilaporkan" value="tidak_hadir" class="sr-only peer"
                                   {{ $curStatus === 'tidak_hadir' ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200 peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 hover:border-red-300 transition-all">
                                <span class="text-sm font-semibold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Tidak Hadir
                                </span>
                            </div>
                        </label>
                    </div>
                    @error('status_guru_dilaporkan')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Sub-Kategori / Alasan jika Tidak Hadir --}}
                <div id="tidak-hadir-section" class="{{ $curStatus === 'tidak_hadir' ? '' : 'hidden' }} p-4 rounded-xl bg-red-50/60 border border-red-100 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-2">Jenis Ketidakhadiran Guru <span class="text-red-500">*</span></label>
                        @php
                            $curAlasan = old('alasan_tidak_hadir') ?? $existingCapture?->alasan_tidak_hadir ?? '';
                            $alasanOptions = [
                                'sakit' => 'Sakit',
                                'izin' => 'Izin',
                                'rapat_dinas' => 'Rapat Dinas',
                                'dinas_luar' => 'Dinas Luar',
                                'tugas_luar' => 'Tugas Luar',
                                'tanpa_keterangan' => 'Tanpa Keterangan (Alpa)',
                            ];
                        @endphp
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                            @foreach($alasanOptions as $val => $label)
                            <label class="relative cursor-pointer">
                                <input type="radio" name="alasan_tidak_hadir" value="{{ $val }}" class="sr-only peer" {{ $curAlasan === $val ? 'checked' : '' }}>
                                <div class="text-center py-2.5 px-3 rounded-xl border border-slate-200 bg-white peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 peer-checked:font-bold hover:border-red-300 transition-all text-xs font-medium text-slate-700 shadow-2xs flex items-center justify-center min-h-[44px]">
                                    {{ $label }}
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @error('alasan_tidak_hadir')<p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Guru Pengganti (Jika Ada)</label>
                        <input type="text" name="guru_pengganti_nama"
                               value="{{ old('guru_pengganti_nama') ?? $existingCapture?->guru_pengganti_nama }}"
                               placeholder="Tulis nama guru yang menggantikan jika ada..."
                               class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                        <p class="text-[11px] text-slate-500 mt-1">Kosongkan bila kelas tidak ada guru pengganti.</p>
                        @error('guru_pengganti_nama')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button type="submit"
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm cursor-pointer">
                    {{ $existingCapture ? 'Perbarui Foto Bukti & Presensi' : 'Kirim Foto Bukti & Presensi' }}
                </button>
            </form>
        </div>
        @else
        <div class="bg-red-50 border border-red-200 rounded-2xl p-5 text-center">
            <svg class="w-10 h-10 text-red-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-red-800 font-semibold mb-1">Akses Ditutup</p>
            <p class="text-red-600 text-sm">Waktu pengisian laporan untuk tanggal ini telah kedaluwarsa (maksimal 7 hari ke belakang).</p>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function formatBytes(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }

        async function handlePhotoSelection(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];
            const originalSize = file.size;

            const previewContainer = document.getElementById('preview-container');
            const previewImg = document.getElementById('preview-img');
            const indicator = document.getElementById('compressing-indicator');
            const compressionStatus = document.getElementById('compression-status');
            const summarySpan = document.getElementById('compression-summary');
            const finalInput = document.getElementById('final-foto-input');

            previewContainer.classList.remove('hidden');
            indicator.classList.remove('hidden');
            compressionStatus.classList.add('hidden');

            try {
                const img = new Image();
                const url = URL.createObjectURL(file);

                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = url;
                });

                // Scale max 1200px
                const maxDim = 1200;
                let width = img.width;
                let height = img.height;

                if (width > maxDim || height > maxDim) {
                    if (width >= height) {
                        height = Math.round((height / width) * maxDim);
                        width = maxDim;
                    } else {
                        width = Math.round((width / height) * maxDim);
                        height = maxDim;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                // Convert to WebP blob (fallback to JPEG)
                const blob = await new Promise((resolve) => {
                    canvas.toBlob((b) => {
                        if (b) {
                            resolve(b);
                        } else {
                            canvas.toBlob((bJpeg) => resolve(bJpeg), 'image/jpeg', 0.82);
                        }
                    }, 'image/webp', 0.82);
                });

                const previewUrl = URL.createObjectURL(blob);
                previewImg.src = previewUrl;

                // Create compressed File and assign to form's file input
                const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
                const compressedFile = new File([blob], `foto_bukti.${ext}`, { type: blob.type });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                finalInput.files = dataTransfer.files;

                const savedPct = Math.max(0, Math.round((1 - (compressedFile.size / originalSize)) * 100));
                summarySpan.textContent = `${formatBytes(originalSize)} ➔ ${formatBytes(compressedFile.size)} (Hemat ${savedPct}%)`;
                compressionStatus.classList.remove('hidden');

                URL.revokeObjectURL(url);
            } catch (err) {
                console.error('Compression error, fallback to raw file:', err);
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                finalInput.files = dataTransfer.files;
                previewImg.src = URL.createObjectURL(file);
            } finally {
                indicator.classList.add('hidden');
            }
        }

        function handleStatusGuru(val) {
            const section = document.getElementById('tidak-hadir-section');
            if (section) {
                section.classList.toggle('hidden', val !== 'tidak_hadir');
            }
        }
    </script>
    @endpush
</x-layouts.siswa>
