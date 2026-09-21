<x-layouts.guru>
    <x-slot:title>{{ $jadwal->mataPelajaran->nama }}</x-slot:title>

    <div class="px-4 py-4 space-y-4 pb-36">
        {{-- Back + header --}}
        <a href="{{ route('guru.dashboard') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 transition-colors mb-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>

        {{-- Info card --}}
        <div class="bg-blue-600 rounded-2xl p-4 text-white">
            <p class="text-lg font-bold">{{ $jadwal->mataPelajaran->nama }}</p>
            <p class="text-sm text-blue-100 mt-0.5">{{ $jadwal->kelas->nama }}</p>
            <div class="flex items-center gap-4 mt-3 text-sm text-blue-100">
                <span>{{ \Carbon\Carbon::parse($jadwal->jam_mulai)->format('H:i') }} – {{ \Carbon\Carbon::parse($jadwal->jam_selesai)->format('H:i') }}</span>
                <span>{{ $tanggal->translatedFormat('l, d F Y') }}</span>
            </div>
        </div>

        <form action="{{ route('guru.pertemuan.save-all', $pertemuan->id) }}" method="POST" id="main-form" class="space-y-4">
            @csrf
            @method('PATCH')

            @if($isLocked)
            <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div>
                    <p class="text-sm font-bold text-red-800">Waktu Pengisian Ditutup</p>
                    <p class="text-xs text-red-600 mt-1">Batas waktu pengisian atau pengubahan data untuk tanggal ini sudah berakhir (maksimal 7 hari ke belakang). Anda hanya dapat melihat data yang sudah ada.</p>
                </div>
            </div>
            @endif

            {{-- ═══ STATUS KEHADIRAN GURU (Disinkronkan dari Siswa) ═══ --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-start gap-2 justify-between">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-slate-900">Status Kehadiran Guru</h2>
                        <p class="text-[11px] text-slate-400 leading-relaxed">Presensi kehadiran guru dicatat secara objektif oleh perwakilan siswa di kelas</p>
                    </div>
                    @php
                        $guruStatus = $pertemuan->kehadiranGuru?->status ?? 'hadir';
                    @endphp
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0 whitespace-nowrap
                        {{ match($guruStatus) {
                            'hadir' => 'badge-hadir',
                            'terlambat' => 'badge-sakit',
                            'sakit' => 'badge-sakit',
                            'dispensasi' => 'badge-dispensasi',
                            default => 'badge-alpa',
                        } }}">
                        {{ match($guruStatus) {
                            'hadir' => 'Hadir',
                            'terlambat' => 'Terlambat',
                            'tidak_hadir' => 'Tidak Hadir',
                            default => ucfirst($guruStatus)
                        } }}
                    </span>
                </div>

                <div class="p-4">
                    <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <div class="w-9 h-9 rounded-xl {{ $guruStatus === 'hadir' ? 'bg-emerald-100 text-emerald-600' : ($guruStatus === 'terlambat' ? 'bg-amber-100 text-amber-600' : 'bg-red-100 text-red-600') }} flex items-center justify-center shrink-0">
                            @if($guruStatus === 'hadir')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            @elseif($guruStatus === 'terlambat')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-700">
                                @if($pertemuan->kehadiranGuru)
                                    Presensi Tercatat: <span class="font-bold text-slate-900">{{ $pertemuan->kehadiranGuru->status_label }}</span>
                                    @if($pertemuan->kehadiranGuru->waktu_hadir)
                                        (pukul {{ $pertemuan->kehadiranGuru->waktu_hadir->format('H:i') }})
                                    @endif
                                @else
                                    Presensi Default: <span class="font-bold text-emerald-600">Hadir</span> (Otomatis)
                                @endif
                            </p>
                            @if($pertemuan->kehadiranGuru?->alasan_tidak_hadir)
                                <p class="text-xs text-red-600 font-medium mt-0.5">Alasan: {{ $pertemuan->kehadiranGuru->alasan_tidak_hadir_label }}</p>
                            @endif
                            @if($pertemuan->kehadiranGuru?->guru_pengganti_nama)
                                <p class="text-xs text-blue-600 mt-0.5">Guru Pengganti di Kelas: <strong>{{ $pertemuan->kehadiranGuru->guru_pengganti_nama }}</strong></p>
                            @endif
                            @if($pertemuan->fotoBuktis->isNotEmpty())
                                <p class="text-[11px] text-slate-500 mt-1">Divalidasi oleh foto bukti perwakilan siswa: <strong>{{ $pertemuan->fotoBuktis->first()->siswa->name ?? 'Siswa' }}</strong></p>
                            @else
                                <p class="text-[11px] text-slate-400 mt-1">Siswa di kelas ini belum mengunggah foto bukti konfirmasi kehadiran.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Preview Foto Bukti dari Siswa --}}
                    @if($pertemuan->fotoBuktis->isNotEmpty())
                    <div class="mt-4 pt-3.5 border-t border-slate-100">
                        <p class="text-xs font-semibold text-slate-800 mb-2 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Foto Bukti Presensi Siswa ({{ $pertemuan->fotoBuktis->count() }})
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @foreach($pertemuan->fotoBuktis as $foto)
                            @php
                                $imgSrc = str_starts_with($foto->foto_path, 'images/') ? asset($foto->foto_path) : Storage::url($foto->foto_path);
                            @endphp
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-2.5 flex items-center gap-3 hover:border-blue-300 transition-all">
                                <a href="{{ $imgSrc }}" target="_blank" title="Klik untuk memperbesar foto" class="w-16 h-16 rounded-lg overflow-hidden bg-slate-200 shrink-0 border border-slate-200 relative group">
                                    <img src="{{ $imgSrc }}" alt="Bukti {{ $foto->siswa->name }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform">
                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </div>
                                </a>
                                <div class="min-w-0 flex-1 space-y-0.5">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ $foto->siswa->name }}</p>
                                    <p class="text-[11px] text-slate-500">Status Lapor: <span class="font-semibold {{ $foto->status_guru_dilaporkan === 'hadir' ? 'text-emerald-700' : 'text-red-600' }}">{{ ucfirst($foto->status_guru_dilaporkan) }}</span></p>
                                    @if($foto->alasan_tidak_hadir)
                                        <p class="text-[10px] text-red-600 font-medium truncate">Alasan: {{ match($foto->alasan_tidak_hadir) {
                                            'sakit' => 'Sakit',
                                            'izin' => 'Izin',
                                            'rapat_dinas' => 'Rapat Dinas',
                                            'dinas_luar' => 'Dinas Luar',
                                            'tugas_luar' => 'Tugas Luar',
                                            'tanpa_keterangan' => 'Tanpa Keterangan',
                                            default => ucfirst($foto->alasan_tidak_hadir)
                                        } }}</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- ═══ JURNAL MENGAJAR ═══ --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-900">Jurnal Mengajar</h2>
                    <span id="draft-status" class="text-[11px] text-emerald-600 font-medium hidden"></span>
                </div>
                <div id="draft-alert" class="hidden m-4 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between text-xs text-amber-900">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Ditemukan draf ketikan yang belum tersimpan (<span id="draft-time"></span>).</span>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" onclick="restoreDraft()" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-semibold rounded-lg transition-colors">
                            Pulihkan
                        </button>
                        <button type="button" onclick="discardDraft()" class="px-2 py-1 text-slate-500 hover:text-slate-800 transition-colors">
                            Abaikan
                        </button>
                    </div>
                </div>
                <div class="p-4 space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Materi Ajar</label>
                        <textarea id="input-materi-ajar" name="materi_ajar" rows="3" placeholder="Tuliskan materi yang diajarkan..."
                                  class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none track-change">{{ $pertemuan->materi_ajar }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Penugasan</label>
                        <textarea id="input-penugasan" name="penugasan" rows="3" placeholder="Tuliskan penugasan untuk siswa (jika ada)..."
                                  class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none track-change">{{ $pertemuan->penugasan }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ═══ DAFTAR SISWA ═══ --}}
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Daftar Kehadiran Siswa</h2>
                        <span class="text-xs text-slate-500" id="siswa-total-count">{{ $pertemuan->kehadiranSiswas->count() }} siswa</span>
                    </div>
                    {{-- Search bar siswa --}}
                    <div class="relative w-full sm:w-64">
                        <input type="text" id="search-siswa-input" oninput="filterSiswaList(this.value)" placeholder="Cari nama atau NIS siswa..."
                               class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                </div>

                @if($pertemuan->kehadiranSiswas->count() > 0)
                <div class="divide-y divide-slate-100 pb-2" id="siswa-list-container">
                    @foreach($pertemuan->kehadiranSiswas->sortBy('siswa.name') as $ks)
                    <div class="px-4 py-3 flex items-center gap-3 siswa-row" data-name="{{ strtolower($ks->siswa->name ?? '') }} {{ strtolower($ks->siswa->siswaProfile?->nis ?? '') }}">
                        {{-- Avatar --}}
                        <div class="w-9 h-9 rounded-full bg-slate-200 flex items-center justify-center shrink-0">
                            <span class="text-xs font-bold text-slate-600">{{ substr($ks->siswa->name ?? '?', 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ $ks->siswa->name }}</p>
                            @if($ks->siswa->siswaProfile?->nis)
                                <p class="text-[11px] text-slate-400">NIS: {{ $ks->siswa->siswaProfile->nis }}</p>
                            @endif
                        </div>
                        {{-- Status dropdown --}}
                        <select name="siswa[{{ $ks->siswa_id }}][status]"
                                class="track-change text-xs font-medium border rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer
                                       {{ match($ks->status) {
                                           'hadir' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                           'sakit' => 'border-amber-200 bg-amber-50 text-amber-800',
                                           'izin' => 'border-sky-200 bg-sky-50 text-sky-800',
                                           'alpa' => 'border-red-200 bg-red-50 text-red-800',
                                           'dispensasi' => 'border-purple-200 bg-purple-50 text-purple-800',
                                           default => 'border-slate-200 bg-slate-50',
                                       } }}">
                            <option value="hadir" {{ $ks->status === 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="sakit" {{ $ks->status === 'sakit' ? 'selected' : '' }}>Sakit</option>
                            <option value="izin" {{ $ks->status === 'izin' ? 'selected' : '' }}>Izin</option>
                            <option value="alpa" {{ $ks->status === 'alpa' ? 'selected' : '' }}>Alpa</option>
                            <option value="dispensasi" {{ $ks->status === 'dispensasi' ? 'selected' : '' }}>Dispensasi</option>
                        </select>
                    </div>
                    @endforeach
                    <div id="no-siswa-found" class="hidden p-6 text-center text-sm text-slate-400">
                        Tidak ada siswa yang cocok dengan pencarian
                    </div>
                </div>
                @else
                <div class="p-6 text-center text-sm text-slate-400">
                    Tidak ada siswa di kelas ini
                </div>
                @endif
            </div>

            {{-- Floating save button (selalu melayang di atas bottom navigation bar) --}}
            @if(!$isLocked)
            <div class="fixed z-40 left-4 right-4 max-w-xl mx-auto bg-white/95 backdrop-blur-md p-3 rounded-2xl border border-slate-200 shadow-[0_8px_30px_rgb(0,0,0,0.18)] flex items-center justify-between"
                 style="bottom: calc(5rem + env(safe-area-inset-bottom, 0px));">
                <div class="flex items-center gap-2 px-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs text-slate-600 font-medium">Pastikan semua data sudah benar</span>
                </div>
                <button type="button"
                        id="btn-submit-main-form"
                        onclick="savePertemuanForm(this)"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white text-sm font-semibold rounded-xl transition-all shadow-sm flex items-center gap-2 cursor-pointer">
                    <span id="btn-text">Simpan Semua</span>
                </button>
            </div>
            @endif
        </form>


    </div>

    @push('scripts')
    <script>
        function handleStatusChange(val) {
            document.getElementById('alpa-section').classList.toggle('hidden', val !== 'alpa');
        }
        function handleJenisAlpa(val) {
            document.getElementById('guru-pengganti-section').classList.toggle('hidden', val !== 'guru_pengganti');
        }
        
        // Update select background color on change
        document.querySelectorAll('select[name^="siswa"]').forEach(sel => {
            sel.addEventListener('change', function() {
                const colors = {
                    hadir: 'border-emerald-200 bg-emerald-50 text-emerald-800',
                    sakit: 'border-amber-200 bg-amber-50 text-amber-800',
                    izin: 'border-sky-200 bg-sky-50 text-sky-800',
                    alpa: 'border-red-200 bg-red-50 text-red-800',
                    dispensasi: 'border-purple-200 bg-purple-50 text-purple-800',
                };
                this.className = `track-change text-xs font-medium border rounded-lg px-2 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-500 ${colors[this.value] || ''}`;
            });
        });

        // Dirty checking / beforeunload warning
        let isDirty = false;
        
        // Track changes on any input with class 'track-change'
        document.querySelectorAll('.track-change').forEach(input => {
            input.addEventListener('change', () => {
                isDirty = true;
            });
            input.addEventListener('input', () => {
                isDirty = true;
            });
        });

        // Auto-save draft in localStorage for materi & penugasan
        const DRAFT_KEY = 'agenda_draft_{{ $pertemuan->id }}';
        const materiInput = document.getElementById('input-materi-ajar');
        const penugasanInput = document.getElementById('input-penugasan');
        const draftAlert = document.getElementById('draft-alert');
        const draftTime = document.getElementById('draft-time');
        const draftStatus = document.getElementById('draft-status');

        let saveTimeout = null;

        function saveDraft() {
            if (!materiInput || !penugasanInput) return;
            const draft = {
                materi_ajar: materiInput.value,
                penugasan: penugasanInput.value,
                time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                timestamp: Date.now()
            };
            localStorage.setItem(DRAFT_KEY, JSON.stringify(draft));
            if (draftStatus) {
                draftStatus.textContent = 'Draf tersimpan lokal ✓';
                draftStatus.classList.remove('hidden');
                setTimeout(() => {
                    draftStatus.classList.add('hidden');
                }, 3000);
            }
        }

        function checkForSavedDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;
                const draft = JSON.parse(raw);
                if (!draft) return;

                const currentMateri = materiInput ? materiInput.value.trim() : '';
                const currentPenugasan = penugasanInput ? penugasanInput.value.trim() : '';
                const draftMateri = (draft.materi_ajar || '').trim();
                const draftPenugasan = (draft.penugasan || '').trim();

                const isDifferent = (draftMateri !== currentMateri) || (draftPenugasan !== currentPenugasan);
                const hasContent = draftMateri.length > 0 || draftPenugasan.length > 0;

                if (isDifferent && hasContent) {
                    if (draftTime) draftTime.textContent = draft.time || 'baru saja';
                    if (draftAlert) draftAlert.classList.remove('hidden');
                }
            } catch (e) {
                console.error('Error reading draft:', e);
            }
        }

        function restoreDraft() {
            try {
                const raw = localStorage.getItem(DRAFT_KEY);
                if (!raw) return;
                const draft = JSON.parse(raw);
                if (materiInput && draft.materi_ajar !== undefined) materiInput.value = draft.materi_ajar;
                if (penugasanInput && draft.penugasan !== undefined) penugasanInput.value = draft.penugasan;
                if (draftAlert) draftAlert.classList.add('hidden');
                isDirty = true;
            } catch (e) {
                console.error('Error restoring draft:', e);
            }
        }

        function discardDraft() {
            localStorage.removeItem(DRAFT_KEY);
            if (draftAlert) draftAlert.classList.add('hidden');
        }

        if (materiInput && penugasanInput) {
            [materiInput, penugasanInput].forEach(el => {
                el.addEventListener('input', () => {
                    clearTimeout(saveTimeout);
                    saveTimeout = setTimeout(saveDraft, 800);
                });
            });
            checkForSavedDraft();
        }

        function savePertemuanForm(btn) {
            const form = document.getElementById('main-form');
            if (!form) return;

            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                const btnText = document.getElementById('btn-text');
                if (btnText) {
                    btnText.innerHTML = `
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg> Menyimpan...
                    `;
                }
            }

            // Clear dirty flag and local draft before sending
            isDirty = false;
            localStorage.removeItem(DRAFT_KEY);

            form.submit();
        }

        // Clear dirty flag and local draft when the form is submitted
        document.getElementById('main-form').addEventListener('submit', () => {
            isDirty = false;
            localStorage.removeItem(DRAFT_KEY);
        });

        // Display confirmation dialog before leaving
        window.addEventListener('beforeunload', (e) => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = ''; // Standard way to show the browser's "Leave site?" prompt
            }
        });

        // Live search filter for student list
        function filterSiswaList(query) {
            const q = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.siswa-row');
            const noFound = document.getElementById('no-siswa-found');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                if (!q || name.includes(q)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noFound) {
                noFound.classList.toggle('hidden', visibleCount > 0);
            }
        }
    </script>
    @endpush
</x-layouts.guru>
