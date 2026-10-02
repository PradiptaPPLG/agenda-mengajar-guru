<x-layouts.siswa>
    <x-slot:title>Foto Bukti</x-slot:title>

    <div class="px-4 py-4 space-y-4">
        <a href="{{ route('siswa.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>

        {{-- Pelajaran info --}}
        <div class="bg-emerald-600 rounded-2xl p-4 text-white">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-lg font-bold">{{ $pertemuan->jadwal->mataPelajaran->nama }}</p>
                    <p class="text-sm text-emerald-100 mt-0.5">Guru: {{ $pertemuan->jadwal->guru->name }}</p>
                </div>
                @if(isset($totalJp) && $totalJp > 1)
                    <span class="px-2.5 py-1 rounded-xl bg-white/20 text-white text-xs font-bold border border-white/30 backdrop-blur-xs">
                        {{ $totalJp }} Jam Pelajaran
                    </span>
                @endif
            </div>
            <div class="flex items-center gap-3 mt-3 text-sm text-emerald-100">
                <span>{{ $jamMulai ?? substr($pertemuan->jadwal->jam_mulai, 0, 5) }} – {{ $jamSelesai ?? substr($pertemuan->jadwal->jam_selesai, 0, 5) }} WIB</span>
                <span>•</span>
                <span>{{ $pertemuan->tanggal->translatedFormat('l, d F Y') }}</span>
            </div>
            @if(isset($totalJp) && $totalJp > 1)
                <div class="mt-2.5 pt-2.5 border-t border-white/20 text-xs text-emerald-100 flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Sesi KBM Tergabung: foto bukti berlaku otomatis untuk seluruh <strong>{{ $totalJp }} JP</strong> sekaligus.</span>
                </div>
            @endif
        </div>

        {{-- Teacher status from the teacher's own record / student sync --}}
        {{-- Teacher status from the teacher's own record / student sync --}}
        @if($pertemuan->kehadiranGuru)
        <div class="bg-white rounded-2xl border border-slate-200 p-4 flex items-start gap-3">
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
            <div class="flex-1 min-w-0">
                <p class="text-xs text-slate-500 font-medium">Status Kehadiran Guru Terkini</p>
                <div class="flex flex-wrap items-center gap-2 mt-0.5">
                    <p class="text-sm font-semibold text-slate-900">{{ $pertemuan->kehadiranGuru->status_label }}</p>
                    @if($pertemuan->kehadiranGuru->waktu_hadir && in_array($pertemuan->kehadiranGuru->status, ['hadir', 'terlambat']))
                        <span class="text-xs text-slate-500 font-medium">
                            (Pukul {{ $pertemuan->kehadiranGuru->waktu_hadir->format('H:i') }} WIB)
                        </span>
                    @endif
                    @if($pertemuan->kehadiranGuru->alasan_tidak_hadir)
                        <span class="text-xs px-2 py-0.5 rounded-md bg-red-50 text-red-700 font-medium border border-red-100">
                            {{ $pertemuan->kehadiranGuru->alasan_tidak_hadir_label }}
                        </span>
                    @endif
                </div>
                @if($pertemuan->kehadiranGuru->keterangan)
                    <p class="text-xs text-slate-600 mt-1.5 bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <span class="font-semibold text-slate-700">Keterangan:</span> {{ $pertemuan->kehadiranGuru->keterangan }}
                    </p>
                @endif
                @if($pertemuan->kehadiranGuru->guru_pengganti_nama)
                    <p class="text-xs text-slate-600 mt-1">Guru Pengganti: <span class="font-medium text-slate-800">{{ $pertemuan->kehadiranGuru->guru_pengganti_nama }}</span></p>
                @endif
            </div>
        </div>
        @endif

        {{-- Notifikasi jika sudah dilaporkan teman sekelas (Point 4) --}}
        @if(!empty($classCapture) && (empty($myCapture) || $myCapture->id !== $classCapture->id))
        <div class="px-4 py-3 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl text-xs flex items-start gap-2.5">
            <svg class="w-4 h-4 shrink-0 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div>
                <p class="font-semibold">Presensi Sudah Dilaporkan</p>
                <p class="mt-0.5 text-amber-800">Laporan kehadiran untuk jam pelajaran ini sudah dikirimkan oleh <strong>{{ $classCapture->siswa->name ?? 'Teman Sekelas' }}</strong> pada pukul {{ $classCapture->created_at->format('H:i') }} WIB.</p>
            </div>
        </div>
        @endif

        {{-- Materi & Tugas dari Guru (Point 5) --}}
        @if($pertemuan->materi_ajar || $pertemuan->penugasan)
        <div class="bg-blue-50/80 border border-blue-200 rounded-2xl p-4 space-y-2">
            <div class="flex items-center gap-2 text-xs font-bold text-blue-900">
                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                Materi & Tugas dari Guru
            </div>
            @if($pertemuan->materi_ajar)
                <div>
                    <p class="text-[11px] font-semibold text-blue-800">Materi Pembelajaran:</p>
                    <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ $pertemuan->materi_ajar }}</p>
                </div>
            @endif
            @if($pertemuan->penugasan)
                <div class="pt-2 border-t border-blue-100">
                    <p class="text-[11px] font-semibold text-amber-900 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Tugas / Instruksi:
                    </p>
                    <p class="text-xs text-slate-700 whitespace-pre-line leading-relaxed">{{ $pertemuan->penugasan }}</p>
                </div>
            @endif
        </div>
        @endif

        {{-- Existing capture preview --}}
        @if($existingCapture)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-900">
                    {{ (!empty($myCapture) && $myCapture->id === $existingCapture->id) ? 'Foto Bukti Anda' : 'Foto Bukti (' . ($existingCapture->siswa->name ?? 'Teman Sekelas') . ')' }}
                </p>
                @if($existingCapture->foto_checkout_path)
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800">
                        Lengkap (Check-in & Check-out)
                    </span>
                @endif
            </div>
            <div class="p-4 bg-slate-50 flex flex-col items-center">
                <div class="grid {{ $existingCapture->foto_checkout_path ? 'grid-cols-2 gap-3' : 'grid-cols-1' }} w-full max-w-md">
                    <div class="text-center">
                        <img src="{{ Storage::url($existingCapture->foto_path) }}" alt="Foto Masuk"
                             class="w-full h-auto max-h-56 object-contain rounded-xl bg-white border border-slate-200">
                        <span class="inline-block mt-1 text-[11px] font-bold text-slate-600">Foto Masuk ({{ $existingCapture->created_at ? $existingCapture->created_at->format('H:i') . ' WIB' : 'Awal' }})</span>
                    </div>
                    @if($existingCapture->foto_checkout_path)
                    <div class="text-center">
                        <img src="{{ Storage::url($existingCapture->foto_checkout_path) }}" alt="Foto Keluar"
                             class="w-full h-auto max-h-56 object-contain rounded-xl bg-white border border-emerald-300">
                        <span class="inline-block mt-1 text-[11px] font-bold text-emerald-700">
                            Foto Check-out ({{ $existingCapture->checkout_at ? $existingCapture->checkout_at->format('H:i') . ' WIB' : 'Akhir' }})
                        </span>
                    </div>
                    @endif
                </div>

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

        {{-- Check-out Section (When enableCheckout is ON, and photo awal is done) --}}
        @if(!$isPast && $enableCheckout && $existingCapture && in_array($existingCapture->status_guru_dilaporkan, ['hadir', 'terlambat']))
        <div id="section-checkout" class="bg-white rounded-2xl border-2 {{ $existingCapture->foto_checkout_path ? 'border-emerald-200' : 'border-emerald-500 shadow-sm' }} overflow-hidden">
            <div class="px-4 py-3 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-emerald-950 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Bukti Foto Check-out (Akhir Jam Pelajaran)
                    </h2>
                    <p class="text-xs text-emerald-700 mt-0.5">Ambil foto guru saat pelajaran selesai / menjelang pulang sebagai bukti guru mengajar hingga tuntas</p>
                </div>
            </div>

            @if(!empty($canCheckout))
            <form action="{{ route('siswa.capture.checkout', ['jadwal' => $pertemuan->jadwal_id, 'tanggal' => $pertemuan->tanggal->toDateString()]) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-4">
                @csrf
                {{-- Hidden final input for checkout --}}
                <input type="file" name="foto_checkout" id="final-checkout-input" class="sr-only">

                {{-- Preview checkout container --}}
                <div id="preview-checkout-container" class="hidden bg-slate-50 rounded-xl border border-slate-200 overflow-hidden">
                    <div class="relative">
                        <img id="preview-checkout-img" src="#" alt="Preview Checkout" class="w-full h-auto max-h-64 object-contain">
                        <div id="compressing-checkout-indicator" class="hidden absolute inset-0 bg-slate-900/40 backdrop-blur-xs flex flex-col items-center justify-center text-white text-xs font-medium">
                            <svg class="animate-spin h-6 w-6 text-white mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Mengompresi foto checkout hemat kuota...
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <label class="flex flex-col items-center justify-center gap-2 py-3.5 border-2 border-dashed border-emerald-300 bg-emerald-50/50 rounded-xl cursor-pointer hover:border-emerald-500 hover:bg-emerald-50 transition-colors">
                        <input type="file" accept="image/*" capture="environment"
                                class="sr-only" onchange="handleCheckoutPhoto(this)">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-xs font-semibold text-emerald-800">Kamera Check-out</span>
                    </label>
                    <label class="flex flex-col items-center justify-center gap-2 py-3.5 border-2 border-dashed border-slate-300 rounded-xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-colors">
                        <input type="file" accept="image/*"
                                class="sr-only" onchange="handleCheckoutPhoto(this)">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-xs font-medium text-slate-600">Galeri Check-out</span>
                    </label>
                </div>

                <button type="submit" id="btn-submit-checkout" disabled
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-semibold text-sm rounded-xl transition-colors">
                    {{ $existingCapture->foto_checkout_path ? 'Perbarui Foto Check-out' : 'Simpan Foto Check-out' }}
                </button>
            </form>
            @else
            <div class="p-6 text-center space-y-2">
                <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">Check-out Belum Dibuka</h4>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    Foto check-out baru dapat diambil mulai pukul <strong class="text-slate-800">{{ $jamCheckoutMulai }} WIB</strong> (15 menit sebelum jam pelajaran berakhir pada pukul {{ $jamSelesai }} WIB).
                </p>
            </div>
            @endif
        </div>
        @endif

        {{-- Upload form (Foto Masuk / Awal) --}}
        @if(!$isPast)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100">
                <h2 class="text-sm font-semibold text-slate-900">
                    {{ !empty($myCapture) ? 'Perbarui Foto Masuk (Awal KBM)' : (!empty($classCapture) ? 'Kirim Foto Bukti Baru (Perbarui Laporan)' : 'Upload Foto Masuk (Awal KBM)') }}
                </h2>
            </div>
            <form action="{{ route('siswa.capture.store', ['jadwal' => $pertemuan->jadwal_id, 'tanggal' => $pertemuan->tanggal->toDateString()]) }}" method="POST" enctype="multipart/form-data" class="p-5 space-y-6" id="capture-form">
                @csrf

                {{-- Camera / Gallery input & Client-Side Compression --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-2">Foto Bukti</label>
                    
                    {{-- Hidden final input submitted with form --}}
                    <input type="file" name="foto" id="final-foto-input" class="sr-only">

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

                {{-- Status per-JP guru --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status Guru Per Jam Pelajaran</label>

                    @if($totalJp > 1)
                    {{-- Multi-JP: per-JP status selector --}}
                    <div class="text-[11px] text-slate-500 mb-2 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Atur status guru per jam pelajaran — tiap JP bisa berbeda.
                    </div>
                    <div class="space-y-2" id="per-jp-container">
                        @foreach($perJpData as $jp)
                        @php
                            $jadwalId = $jp['jadwal']->id;
                            $jpIndex = $jp['jp_index'];
                            // Smart default: JP1 use existing or hadir, JP2+ default hadir
                            $defaultStatus = $jp['current_status'] ?? ($jpIndex === 1 ? (old("status_jp.{$jadwalId}") ?? 'hadir') : (old("status_jp.{$jadwalId}") ?? 'hadir'));
                        @endphp
                        <div class="rounded-xl border border-slate-200 bg-slate-50 overflow-hidden jp-row" data-jp="{{ $jpIndex }}" data-jadwal="{{ $jadwalId }}">
                            <div class="flex items-center justify-between px-3 py-2 bg-white border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 text-[11px] font-bold flex items-center justify-center shrink-0">{{ $jpIndex }}</span>
                                    <span class="text-xs font-semibold text-slate-700">JP{{ $jpIndex }}</span>
                                    <span class="text-[11px] text-slate-400">{{ $jp['jam_mulai'] }}–{{ $jp['jam_selesai'] }}</span>
                                </div>
                                {{-- Status badge shown when collapsed --}}
                                <span class="jp-status-badge text-[11px] font-bold px-2 py-0.5 rounded-full" data-jadwal="{{ $jadwalId }}"></span>
                            </div>
                            <div class="px-3 py-2.5">
                                <div class="grid grid-cols-3 gap-1.5">
                                    @php
                                        $isLateDisabled = $jp['is_within_tolerance'];
                                        $jpStatusOptions = [
                                            'hadir'       => ['Hadir',       'border-emerald-500 bg-emerald-50 text-emerald-700', 'hover:border-emerald-300'],
                                            'terlambat'   => ['Terlambat',   'border-amber-500 bg-amber-50 text-amber-700',       'hover:border-amber-300'],
                                            'tidak_hadir' => ['Tidak Hadir', 'border-red-500 bg-red-50 text-red-700',             'hover:border-red-300'],
                                        ];
                                        $jpColorMap = ['hadir' => 'emerald', 'terlambat' => 'amber', 'tidak_hadir' => 'red'];
                                        $jpLabelMap = ['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'tidak_hadir' => 'Tidak Hadir'];
                                    @endphp
                                    @foreach($jpStatusOptions as $jpVal => [$jpLabel, $jpCheckedClass, $jpHoverClass])
                                    @if($jpVal === 'terlambat' && $isLateDisabled)
                                    <label class="relative cursor-not-allowed opacity-50" title="Masih dalam batas toleransi ({{ $jp['toleransi_menit'] }} menit, s.d. {{ $jp['deadline_toleransi'] }} WIB). Tombol terlambat dinonaktifkan.">
                                        <input type="radio"
                                               name="status_jp[{{ $jadwalId }}]"
                                               value="{{ $jpVal }}"
                                               disabled
                                               class="sr-only">
                                        <div class="text-center py-2 px-1 rounded-lg border border-slate-200 bg-slate-100 text-[11px] font-semibold text-slate-400">
                                            {{ $jpLabel }}
                                            <span class="block text-[9px] text-amber-700 font-normal leading-tight">s.d. {{ $jp['deadline_toleransi'] }}</span>
                                        </div>
                                    </label>
                                    @else
                                    <label class="relative cursor-pointer">
                                        <input type="radio"
                                               name="status_jp[{{ $jadwalId }}]"
                                               value="{{ $jpVal }}"
                                               class="sr-only peer jp-status-radio"
                                               data-jadwal="{{ $jadwalId }}"
                                               data-color="{{ $jpColorMap[$jpVal] }}"
                                               data-label="{{ $jpLabel }}"
                                               {{ $defaultStatus === $jpVal ? 'checked' : '' }}
                                               onchange="handleJpStatus({{ $jadwalId }}, this.value, '{{ $jpColorMap[$jpVal] }}', '{{ $jpLabel }}')">
                                        <div class="text-center py-2 rounded-lg border border-slate-200 bg-white {{ $jpHoverClass }} transition-all text-[11px] font-semibold text-slate-500
                                                    peer-checked:{{ $jpCheckedClass }}">
                                            {{ $jpLabel }}
                                        </div>
                                    </label>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- Keterangan Terlambat (Multi-JP) --}}
                    <div id="terlambat-section-multi" class="{{ collect($perJpData)->contains(fn($jp) => $jp['current_status'] === 'terlambat') ? '' : 'hidden' }} mt-3 p-4 rounded-xl bg-amber-50/70 border border-amber-200 space-y-2">
                        <div class="flex items-center gap-1.5 text-xs font-bold text-amber-800">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Keterangan Guru Terlambat (Opsional)
                        </div>
                        <input type="text" name="keterangan_terlambat"
                               value="{{ old('keterangan_terlambat') ?? collect($perJpData)->map(fn($jp) => $jp['capture']?->keterangan)->filter()->first() }}"
                               placeholder="Contoh: Guru terlambat hadir karena kemacetan / urusan dinas..."
                               class="w-full px-3.5 py-2.5 bg-white border border-amber-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <p class="text-[11px] text-amber-700">Keterangan ini menjelaskan alasan atau catatan keterlambatan guru di jam pelajaran.</p>
                    </div>

                    {{-- Alasan tidak hadir (Multi-JP) --}}
                    <div id="tidak-hadir-section" class="{{ collect($perJpData)->contains(fn($jp) => in_array($jp['current_status'], ['tidak_hadir','sakit','alpa','dispensasi'])) ? '' : 'hidden' }} mt-3 p-4 rounded-xl bg-red-50/60 border border-red-100 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-bold text-red-700 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Detail Ketidakhadiran Guru (Per Jam Pelajaran)
                            </p>
                            <span class="text-[10px] text-red-700 font-bold px-2 py-0.5 bg-red-100/70 border border-red-200 rounded-md" id="badge-jp-tidak-hadir"></span>
                        </div>
                        <p class="text-[11px] text-slate-500">Pilih jenis keterangan ketidakhadiran guru yang berlaku untuk jam pelajaran yang ditandai <strong>Tidak Hadir</strong>.</p>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Jenis Ketidakhadiran Guru <span class="text-red-500">*</span></label>
                            @php
                                $curAlasan = old('alasan_tidak_hadir') ?? collect($perJpData)->map(fn($jp) => $jp['capture']?->alasan_tidak_hadir)->filter()->first() ?? '';
                                $alasanOptions = [
                                    'sakit' => 'Sakit',
                                    'izin' => 'Izin',
                                    'cuti' => 'Cuti',
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
                                    <div class="text-center py-2.5 px-3 rounded-xl border border-slate-200 bg-white peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 peer-checked:font-bold hover:border-red-300 transition-all text-xs font-medium text-slate-700 shadow-2xs flex items-center justify-center min-h-[40px]">
                                        {{ $label }}
                                    </div>
                                </label>
                                @endforeach
                            </div>
                            @error('alasan_tidak_hadir')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan / Catatan Ketidakhadiran (Opsional)</label>
                            <input type="text" name="keterangan"
                                   value="{{ old('keterangan') ?? collect($perJpData)->map(fn($jp) => $jp['capture']?->keterangan)->filter()->first() }}"
                                   placeholder="Contoh: Cuti tahunan / Sakit ada surat dokter / Izin dinas..."
                                   class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Guru Pengganti (Jika Ada)</label>
                            <input type="text" name="guru_pengganti_nama"
                                   value="{{ old('guru_pengganti_nama') ?? collect($perJpData)->map(fn($jp) => $jp['capture']?->guru_pengganti_nama)->filter()->first() }}"
                                   placeholder="Tulis nama guru yang menggantikan jika ada..."
                                   class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>{{-- end #tidak-hadir-section multi-JP --}}

                    @else
                    {{-- Single JP: simple global selector (backward compatible) --}}
                    @php
                        $curStatus = old('status_guru_dilaporkan') ?? $existingCapture?->status_guru_dilaporkan ?? 'hadir';
                        if (in_array($curStatus, ['sakit', 'alpa', 'dispensasi'])) { $curStatus = 'tidak_hadir'; }
                        $isSingleLateDisabled = $isWithinToleranceUtama;
                    @endphp
                    <div class="grid grid-cols-3 gap-2">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="status_guru_dilaporkan" value="hadir" class="sr-only peer"
                                   {{ $curStatus === 'hadir' ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200
                                        peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700
                                        hover:border-emerald-300 transition-all">
                                <span class="text-sm font-semibold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Hadir
                                </span>
                            </div>
                        </label>
                        @if($isSingleLateDisabled)
                        <label class="relative cursor-not-allowed opacity-50" title="Masih dalam batas toleransi ({{ $toleransiUtamaMenit }} menit, s.d. {{ $deadlineToleransiUtama }} WIB). Tombol terlambat dinonaktifkan.">
                            <input type="radio" name="status_guru_dilaporkan" value="terlambat" disabled class="sr-only">
                            <div class="text-center py-2 px-1 rounded-xl border-2 border-slate-200 bg-slate-100 text-slate-400">
                                <span class="text-xs font-semibold block text-slate-400">Terlambat</span>
                                <span class="text-[9px] text-amber-700 font-normal block leading-tight">s.d. {{ $deadlineToleransiUtama }}</span>
                            </div>
                        </label>
                        @else
                        <label class="relative cursor-pointer">
                            <input type="radio" name="status_guru_dilaporkan" value="terlambat" class="sr-only peer"
                                   {{ $curStatus === 'terlambat' ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200
                                        peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700
                                        hover:border-amber-300 transition-all">
                                <span class="text-sm font-semibold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    Terlambat
                                </span>
                            </div>
                        </label>
                        @endif
                        <label class="relative cursor-pointer">
                            <input type="radio" name="status_guru_dilaporkan" value="tidak_hadir" class="sr-only peer"
                                   {{ $curStatus === 'tidak_hadir' ? 'checked' : '' }}
                                   onchange="handleStatusGuru(this.value)">
                            <div class="text-center py-3 rounded-xl border-2 border-slate-200
                                        peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700
                                        hover:border-red-300 transition-all">
                                <span class="text-sm font-semibold flex items-center justify-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Tidak Hadir
                                </span>
                            </div>
                        </label>
                    </div>

                    {{-- Keterangan terlambat for single JP --}}
                    <div id="terlambat-section-single" class="{{ $curStatus === 'terlambat' ? '' : 'hidden' }} mt-3 p-4 rounded-xl bg-amber-50/70 border border-amber-200 space-y-2">
                        <label class="block text-xs font-semibold text-amber-900">Keterangan Keterlambatan Guru (Opsional)</label>
                        <input type="text" name="keterangan_terlambat"
                               value="{{ old('keterangan_terlambat') ?? $existingCapture?->keterangan }}"
                               placeholder="Contoh: Guru terlambat 15 menit karena jalan macet / urusan dinas..."
                               class="w-full px-3.5 py-2.5 bg-white border border-amber-200 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-amber-500">
                    </div>

                    {{-- Alasan tidak hadir for single JP --}}
                    <div id="tidak-hadir-section-single" class="{{ $curStatus === 'tidak_hadir' ? '' : 'hidden' }} mt-3 p-4 rounded-xl bg-red-50/60 border border-red-100 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-2">Jenis Ketidakhadiran Guru <span class="text-red-500">*</span></label>
                            @php
                                $curAlasan = old('alasan_tidak_hadir') ?? $existingCapture?->alasan_tidak_hadir ?? '';
                                $alasanOptions = [
                                    'sakit' => 'Sakit',
                                    'izin' => 'Izin',
                                    'cuti' => 'Cuti',
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
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Keterangan / Catatan Ketidakhadiran (Opsional)</label>
                            <input type="text" name="keterangan"
                                   value="{{ old('keterangan') ?? $existingCapture?->keterangan }}"
                                   placeholder="Contoh: Cuti tahunan / Sakit ada surat dokter / Izin dinas..."
                                   class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
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
                    @endif
                </div>

                <button type="submit" id="btn-submit-capture"
                        class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl transition-colors shadow-sm cursor-pointer disabled:bg-slate-300 disabled:cursor-not-allowed">
                    {{ !empty($myCapture) ? 'Perbarui Foto Bukti & Presensi' : 'Kirim Foto Bukti & Presensi' }}
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
            const btnSubmit = document.getElementById('btn-submit-capture');

            previewContainer.classList.remove('hidden');
            indicator.classList.remove('hidden');
            compressionStatus.classList.add('hidden');
            if (btnSubmit) {
                btnSubmit.disabled = true;
            }

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
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                }
            }
        }

        async function handleCheckoutPhoto(input) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            const previewContainer = document.getElementById('preview-checkout-container');
            const previewImg = document.getElementById('preview-checkout-img');
            const indicator = document.getElementById('compressing-checkout-indicator');
            const finalInput = document.getElementById('final-checkout-input');
            const btnSubmit = document.getElementById('btn-submit-checkout');

            previewContainer.classList.remove('hidden');
            indicator.classList.remove('hidden');

            try {
                const img = new Image();
                const url = URL.createObjectURL(file);

                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = url;
                });

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

                const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
                const compressedFile = new File([blob], `foto_checkout.${ext}`, { type: blob.type });

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(compressedFile);
                finalInput.files = dataTransfer.files;

                if (btnSubmit) {
                    btnSubmit.disabled = false;
                }

                URL.revokeObjectURL(url);
            } catch (err) {
                console.error('Compression error for checkout, fallback to raw file:', err);
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                finalInput.files = dataTransfer.files;
                previewImg.src = URL.createObjectURL(file);
                if (btnSubmit) {
                    btnSubmit.disabled = false;
                }
            } finally {
                indicator.classList.add('hidden');
            }
        }

        function handleStatusGuru(val) {
            const sectionTidakHadir = document.getElementById('tidak-hadir-section-single');
            const sectionTerlambat = document.getElementById('terlambat-section-single');
            if (sectionTidakHadir) {
                sectionTidakHadir.classList.toggle('hidden', val !== 'tidak_hadir');
            }
            if (sectionTerlambat) {
                sectionTerlambat.classList.toggle('hidden', val !== 'terlambat');
            }
        }

        // Per-JP status handler
        function handleJpStatus(jadwalId, val, color, label) {
            // Update badge
            updateJpBadge(jadwalId, val, color, label);

            // Show/hide sections
            checkAnyTidakHadir();
            checkAnyTerlambat();
        }

        function checkAnyTerlambat() {
            const radios = document.querySelectorAll('.jp-status-radio:checked');
            let anyTerlambat = false;
            radios.forEach(r => {
                if (r.value === 'terlambat') anyTerlambat = true;
            });
            const section = document.getElementById('terlambat-section-multi');
            if (section) {
                section.classList.toggle('hidden', !anyTerlambat);
            }
        }

        function updateJpBadge(jadwalId, val, color, label) {
            const badge = document.querySelector(`.jp-status-badge[data-jadwal="${jadwalId}"]`);
            if (!badge) return;

            const colorMap = {
                emerald: 'bg-emerald-100 text-emerald-700',
                amber: 'bg-amber-100 text-amber-700',
                red: 'bg-red-100 text-red-700',
            };
            badge.className = `jp-status-badge text-[11px] font-bold px-2 py-0.5 rounded-full ${colorMap[color] || ''}`;
            badge.textContent = label;
        }

        function checkAnyTidakHadir() {
            const radios = document.querySelectorAll('.jp-status-radio:checked');
            let anyTidakHadir = false;
            const jpTidakHadirList = [];
            radios.forEach(r => {
                if (r.value === 'tidak_hadir') {
                    anyTidakHadir = true;
                    const jpRow = r.closest('.jp-row');
                    if (jpRow) {
                        jpTidakHadirList.push('JP ' + jpRow.dataset.jp);
                    }
                }
            });
            const section = document.getElementById('tidak-hadir-section');
            const badge = document.getElementById('badge-jp-tidak-hadir');
            if (section) {
                section.classList.toggle('hidden', !anyTidakHadir);
            }
            if (badge) {
                badge.textContent = jpTidakHadirList.length > 0 ? ('Berlaku: ' + jpTidakHadirList.join(', ')) : '';
            }
        }

        // Initialize badges and sections on page load
        document.addEventListener('DOMContentLoaded', function () {
            const colorMap = { emerald: 'bg-emerald-100 text-emerald-700', amber: 'bg-amber-100 text-amber-700', red: 'bg-red-100 text-red-700' };
            document.querySelectorAll('.jp-status-radio:checked').forEach(radio => {
                const jadwalId = radio.dataset.jadwal;
                const color = radio.dataset.color;
                const label = radio.dataset.label;
                updateJpBadge(jadwalId, radio.value, color, label);
            });
            checkAnyTidakHadir();
            checkAnyTerlambat();
        });

        document.getElementById('capture-form')?.addEventListener('submit', function(e) {
            const finalInput = document.getElementById('final-foto-input');
            const hasExisting = {{ !empty($myCapture) ? 'true' : 'false' }};
            if (!hasExisting && (!finalInput.files || finalInput.files.length === 0)) {
                e.preventDefault();
                alert('Silakan ambil foto bukti kehadiran guru terlebih dahulu melalui kamera atau galeri.');
                return false;
            }

            // Validate: if any JP has tidak_hadir, alasan must be selected
            const anyTidakHadir = Array.from(document.querySelectorAll('.jp-status-radio:checked'))
                .some(r => r.value === 'tidak_hadir');
            const singleTidakHadir = document.querySelector('input[name="status_guru_dilaporkan"]:checked')?.value === 'tidak_hadir';

            if ((anyTidakHadir || singleTidakHadir) && !document.querySelector('input[name="alasan_tidak_hadir"]:checked')) {
                e.preventDefault();
                alert('Silakan pilih jenis ketidakhadiran guru untuk JP yang ditandai Tidak Hadir.');
                return false;
            }
        });

        document.querySelector('#section-checkout form')?.addEventListener('submit', function(e) {
            const finalCheckout = document.getElementById('final-checkout-input');
            if (!finalCheckout.files || finalCheckout.files.length === 0) {
                e.preventDefault();
                alert('Silakan ambil foto bukti check-out terlebih dahulu melalui kamera atau galeri.');
                return false;
            }
        });

    </script>
    @endpush
</x-layouts.siswa>
